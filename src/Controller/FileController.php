<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Repository\FileRepository;
use App\Repository\ShareRepository;
use App\Service\ChunkUploadService;
use App\Service\FileStorageService;
use App\Config\Config;
use App\Core\View;

final class FileController
{
    public function dashboard(): void
    {
        $repo = new FileRepository();
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) Config::get('FILES_PER_PAGE', 10)));

        $totalFiles = $repo->countAll($search);
        $totalPages = max(1, (int) ceil($totalFiles / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $files = $repo->findPaginated($perPage, $offset, $search);

        $startItem = $totalFiles > 0 ? $offset + 1 : 0;
        $endItem = min($offset + count($files), $totalFiles);

        $storage = new FileStorageService();
        $phpMax = View::phpUploadMaxBytes();
        $appMax = (int) Config::get('MAX_UPLOAD_SIZE', 104857600);
        $effectiveMax = min($phpMax, $appMax);

        $shareRepo = new ShareRepository();
        $sharesByFileId = [];
        foreach ($files as $f) {
            $fid = (int) $f['id'];
            $sharesByFileId[$fid] = $shareRepo->findActiveByFileId($fid);
        }

        View::render('file/dashboard', [
            'user' => Auth::user(),
            'files' => $files,
            'csrf' => Csrf::token(),
            'flash' => Flash::get(),
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalFiles' => $totalFiles,
            'totalPages' => $totalPages,
            'startItem' => $startItem,
            'endItem' => $endItem,
            'maxUploadSize' => View::formatBytes($effectiveMax),
            'allowedExtensions' => $storage->getAllowedExtensions(),
            'sharesByFileId' => $sharesByFileId,
            'appUrl' => rtrim((string) Config::get('APP_URL', ''), '/') . '/',
        ]);
    }

    public function upload(): void
    {
        $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Jeton CSRF invalide.',
                ]);
                return;
            }

            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=dashboard');
        }

        if (!isset($_FILES['file'])) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Aucun fichier envoyé.',
                ]);
                return;
            }

            Flash::set('danger', 'Aucun fichier envoyé.');
            Response::redirect('?action=dashboard');
        }

        try {
            $storage = new FileStorageService();
            $file = $storage->store($_FILES['file']);

            $repo = new FileRepository();
            $repo->create([
                'original_name' => $file['original_name'],
                'stored_name' => $file['stored_name'],
                'mime_type' => $file['mime_type'],
                'extension' => $file['extension'],
                'size_bytes' => $file['size_bytes'],
                'sha256' => $file['sha256'],
                'storage_path' => $file['storage_path'],
                'uploaded_by' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'Fichier uploadé avec succès.',
                ]);
                return;
            }

            Flash::set('success', 'Fichier uploadé avec succès.');
        } catch (\Throwable $e) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
                return;
            }

            Flash::set('danger', $e->getMessage());
        }

        Response::redirect('?action=dashboard');
    }

    public function uploadChunkInit(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide.']);
            return;
        }

        $originalName = trim($_POST['original_name'] ?? '');
        $totalSize    = (int) ($_POST['total_size'] ?? 0);
        $totalChunks  = (int) ($_POST['total_chunks'] ?? 0);

        if ($originalName === '' || $totalSize <= 0 || $totalChunks < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
            return;
        }

        try {
            $storage   = new FileStorageService();
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $storage->getAllowedExtensions(), true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Extension non autorisée.']);
                return;
            }

            $maxSize = (int) Config::get('MAX_UPLOAD_SIZE', 104857600);

            if ($totalSize > $maxSize) {
                $mb = round($maxSize / 1024 / 1024, 1);
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Fichier trop volumineux. Maximum autorisé : {$mb} Mo."]);
                return;
            }

            $chunkService = new ChunkUploadService();
            $uploadId     = $chunkService->initSession($originalName, $totalSize, $totalChunks);

            echo json_encode(['success' => true, 'upload_id' => $uploadId]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function uploadChunk(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide.']);
            return;
        }

        $uploadId   = $_POST['upload_id'] ?? '';
        $chunkIndex = (int) ($_POST['chunk_index'] ?? -1);

        if (!preg_match('/^[a-f0-9]{32}$/', $uploadId) || $chunkIndex < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
            return;
        }

        if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Chunk manquant ou invalide.']);
            return;
        }

        try {
            $chunkService = new ChunkUploadService();
            $chunkService->pruneExpired();
            $received = $chunkService->storeChunk($uploadId, $chunkIndex, $_FILES['chunk']['tmp_name']);

            echo json_encode(['success' => true, 'received' => $received]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function uploadChunkFinalize(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide.']);
            return;
        }

        $uploadId = $_POST['upload_id'] ?? '';

        if (!preg_match('/^[a-f0-9]{32}$/', $uploadId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Upload ID invalide.']);
            return;
        }

        $chunkService = new ChunkUploadService();

        try {
            $meta          = $chunkService->getMeta($uploadId);
            $assembledPath = $chunkService->assemble($uploadId);

            $storage = new FileStorageService();
            $file    = $storage->storeAssembled(
                $assembledPath,
                (string) $meta['original_name'],
                (int)    $meta['total_size']
            );

            $repo = new FileRepository();
            $repo->create([
                'original_name' => $file['original_name'],
                'stored_name'   => $file['stored_name'],
                'mime_type'     => $file['mime_type'],
                'extension'     => $file['extension'],
                'size_bytes'    => $file['size_bytes'],
                'sha256'        => $file['sha256'],
                'storage_path'  => $file['storage_path'],
                'uploaded_by'   => Auth::id(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            echo json_encode(['success' => true, 'message' => 'Fichier uploadé avec succès.']);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } finally {
            $chunkService->cleanup($uploadId);
        }
    }

    public function download(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        $repo = new FileRepository();
        $file = $repo->findById($id);

        if (!$file || !is_file($file['storage_path'])) {
            http_response_code(404);
            echo 'Fichier introuvable.';
            return;
        }

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
        header('Content-Length: ' . filesize($file['storage_path']));
        readfile($file['storage_path']);
        exit;
    }

    public function delete(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=dashboard');
        }

        $id = (int) ($_POST['id'] ?? 0);

        $repo = new FileRepository();
        $file = $repo->findById($id);

        if (!$file) {
            Flash::set('danger', 'Fichier introuvable.');
            Response::redirect('?action=dashboard');
        }

        if (is_file($file['storage_path'])) {
            unlink($file['storage_path']);
        }

        (new ShareRepository())->deleteByFileId($id);
        $repo->delete($id);

        Flash::set('success', 'Fichier supprimé.');
        Response::redirect('?action=dashboard');
    }

    public function preview(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $inline = ($_GET['inline'] ?? '') === '1';

        $repo = new FileRepository();
        $file = $repo->findById($id);

        if (!$file || !is_file($file['storage_path'])) {
            http_response_code(404);

            if ($inline) {
                echo 'Fichier introuvable.';
                return;
            }

            header('Content-Type: text/html; charset=UTF-8');
            echo '<div class="file-preview-empty">Fichier introuvable.</div>';
            return;
        }

        $previewType = $this->detectPreviewType($file);

        if ($inline) {
            if (!in_array($previewType, ['image', 'pdf'], true)) {
                http_response_code(415);
                echo 'Aperçu inline non disponible.';
                return;
            }

            $mimeType = $previewType === 'pdf'
                ? 'application/pdf'
                : (string) ($file['mime_type'] ?? 'application/octet-stream');

            $fileName = str_replace(['"', "\r", "\n"], '', basename((string) ($file['original_name'] ?? 'file')));

            header('X-Content-Type-Options: nosniff');
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($file['storage_path']));

            readfile($file['storage_path']);
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $this->buildPreviewHtml($file, $previewType);
    }

    private function detectPreviewType(array $file): string
    {
        $mimeType = strtolower((string) ($file['mime_type'] ?? ''));
        $extension = strtolower((string) ($file['extension'] ?? ''));

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $textExtensions = ['txt', 'log', 'csv', 'json', 'xml', 'md'];

        $textMimeTypes = [
            'application/json',
            'application/xml',
            'text/xml',
            'text/csv',
            'text/markdown',
        ];

        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return 'pdf';
        }

        if (
            (str_starts_with($mimeType, 'image/') && $extension !== 'svg')
            || in_array($extension, $imageExtensions, true)
        ) {
            return 'image';
        }

        if (
            str_starts_with($mimeType, 'text/')
            || in_array($mimeType, $textMimeTypes, true)
            || in_array($extension, $textExtensions, true)
        ) {
            return 'text';
        }

        return 'unsupported';
    }

    private function buildPreviewHtml(array $file, string $previewType): string
    {
        $fileId = (int) ($file['id'] ?? 0);
        $originalName = (string) ($file['original_name'] ?? 'Fichier');
        $inlineUrl = '?action=preview&id=' . $fileId . '&inline=1';
        $downloadUrl = '?action=download&id=' . $fileId;

        if ($previewType === 'image') {
            return '
                <div class="file-preview-pane">
                    <img
                        src="' . View::e($inlineUrl) . '"
                        alt="' . View::e($originalName) . '"
                        class="file-preview-image"
                    >
                </div>
                <div class="file-preview-note mt-3 text-center">
                    Cliquez sur l’image pour zoomer ou dézoomer.
                </div>
            ';
        }

        if ($previewType === 'pdf') {
            return '
                <div class="file-preview-pane">
                    <iframe
                        src="' . View::e($inlineUrl) . '"
                        class="file-preview-frame"
                        title="' . View::e($originalName) . '"
                    ></iframe>
                </div>
            ';
        }

        if ($previewType === 'text') {
            $maxBytes = 200000;
            $filePath = (string) $file['storage_path'];
            $fileSize = (int) filesize($filePath);
            $content = file_get_contents($filePath, false, null, 0, $maxBytes);

            if ($content === false) {
                return '
                    <div class="file-preview-empty">
                        Impossible de lire ce fichier texte.
                    </div>
                ';
            }

            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $escapedContent = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $isTruncated = $fileSize > $maxBytes;

            return '
                <div class="file-preview-pane">
                    ' . ($isTruncated
                        ? '<div class="file-preview-note">Aperçu limité aux premiers ' . number_format($maxBytes, 0, ',', ' ') . ' octets.</div>'
                        : '') . '
                    <pre class="file-preview-text">' . $escapedContent . '</pre>
                </div>
            ';
        }

        return '
            <div class="file-preview-empty">
                <div class="mb-2"><i class="bi bi-file-earmark"></i></div>
                <div class="mb-3">Aperçu non disponible pour ce type de fichier.</div>
                <a href="' . View::e($downloadUrl) . '" class="btn btn-orange">
                    Télécharger le fichier
                </a>
            </div>
        ';
    }

    public function simpleList(): void
    {
        $repo = new FileRepository();
        $search = trim($_GET['search'] ?? '');

        $totalFiles = $repo->countAll($search);
        $files = $repo->findPaginated($totalFiles > 0 ? $totalFiles : 1, 0, $search);

        View::render('file/simple-list', [
            'user' => Auth::user(),
            'files' => $files,
            'flash' => Flash::get(),
            'search' => $search,
            'totalFiles' => $totalFiles,
            'startItem' => $totalFiles > 0 ? 1 : 0,
            'endItem' => $totalFiles,
        ]);
    }

    /*
    public function simpleList(): void
    {
        $repo = new FileRepository();
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) Config::get('FILES_PER_PAGE', 10)));

        $totalFiles = $repo->countAll($search);
        $totalPages = max(1, (int) ceil($totalFiles / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $files = $repo->findPaginated($perPage, $offset, $search);

        $startItem = $totalFiles > 0 ? $offset + 1 : 0;
        $endItem = min($offset + count($files), $totalFiles);

        View::render('file/simple-list', [
            'user' => Auth::user(),
            'files' => $files,
            'flash' => Flash::get(),
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalFiles' => $totalFiles,
            'totalPages' => $totalPages,
            'startItem' => $startItem,
            'endItem' => $endItem,
        ]);
    }
    */
}