<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Repository\FileRepository;
use App\Service\FileStorageService;
use App\Config\Config;
use App\Core\View;

final class FileController
{
    public function dashboard(): void
    {
        $repo = new FileRepository();
        $search = trim($_GET['search'] ?? '');

        $phpMax = View::phpUploadMaxBytes();
        $appMax = (int) Config::get('MAX_UPLOAD_SIZE', 104857600);
        $effectiveMax = min($phpMax, $appMax);

        View::render('file/dashboard', [
            'user' => Auth::user(),
            'files' => $repo->all($search),
            'csrf' => Csrf::token(),
            'flash' => Flash::get(),
            'search' => $search,
            'maxUploadSize' => View::formatBytes($effectiveMax),
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

        $repo->delete($id);

        Flash::set('success', 'Fichier supprimé.');
        Response::redirect('?action=dashboard');
    }
}