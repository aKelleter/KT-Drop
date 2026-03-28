<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\ApiResponse;
use App\Core\View;
use App\Repository\FileRepository;
use App\Repository\ShareRepository;
use App\Service\FileStorageService;

final class FileApiController extends BaseApiController
{
    private const PER_PAGE = 20;

    /**
     * GET /api/v1/files
     * Query params: page, search, category
     */
    public function index(array $_params): void
    {
        $this->authenticate();
        $repo     = new FileRepository();
        $search   = ($_GET['search'] ?? '') !== '' ? $_GET['search'] : null;
        $catId    = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = self::PER_PAGE;
        $total    = $repo->countAll($search, $catId);
        $files    = $repo->findPaginated($perPage, ($page - 1) * $perPage, $search, $catId);

        ApiResponse::paginated(
            array_map([$this, 'formatFile'], $files),
            $total,
            $page,
            $perPage
        );
    }

    /**
     * GET /api/v1/files/all
     * Retourne tous les fichiers sans pagination.
     */
    public function all(array $_params): void
    {
        $this->authenticate();
        $repo   = new FileRepository();
        $search = ($_GET['search'] ?? '') !== '' ? $_GET['search'] : null;
        $catId  = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
        $files  = $repo->all($search, $catId);

        ApiResponse::json(array_map([$this, 'formatFile'], $files));
    }

    /**
     * GET /api/v1/files/{id}
     */
    public function show(array $params): void
    {
        $this->authenticate();
        $file = (new FileRepository())->findById($this->intParam($params, 'id'));

        if (!$file) {
            ApiResponse::error('Fichier introuvable', 404);
        }

        ApiResponse::json($this->formatFile($file));
    }

    /**
     * POST /api/v1/files
     * multipart/form-data: file (required), category_id (optional)
     */
    public function store(array $_params): void
    {
        $user = $this->authenticate();
        $uploaded = $_FILES['file'] ?? null;

        if (!$uploaded || $uploaded['error'] !== UPLOAD_ERR_OK) {
            ApiResponse::error('Fichier manquant ou erreur lors de l\'upload', 422);
        }

        $catId   = isset($_POST['category_id']) && $_POST['category_id'] !== ''
                     ? (int) $_POST['category_id'] : null;

        try {
            $storage = new FileStorageService();
            $stored  = $storage->store($uploaded);
            $repo    = new FileRepository();

            $repo->create([
                'original_name' => $stored['original_name'],
                'stored_name'   => $stored['stored_name'],
                'mime_type'     => $stored['mime_type'],
                'extension'     => $stored['extension'],
                'size_bytes'    => $stored['size_bytes'],
                'sha256'        => $stored['sha256'],
                'storage_path'  => $stored['storage_path'],
                'uploaded_by'   => (int) $user['user_id'],
                'created_at'    => date('Y-m-d H:i:s'),
                'category_id'   => $catId,
            ]);

            $id   = (int) \App\Core\Database::connection()->lastInsertId();
            $file = $repo->findById($id);
            ApiResponse::json($this->formatFile($file), 201);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * PATCH /api/v1/files/{id}
     * JSON body: { "name": "...", "category_id": 1|null }
     */
    public function update(array $params): void
    {
        $this->authenticate();
        $repo = new FileRepository();
        $file = $repo->findById($this->intParam($params, 'id'));

        if (!$file) {
            ApiResponse::error('Fichier introuvable', 404);
        }

        $body  = $this->body();
        $name  = trim((string) ($body['name'] ?? $file['original_name']));
        $catId = array_key_exists('category_id', $body)
                   ? ($body['category_id'] !== null && $body['category_id'] !== '' ? (int) $body['category_id'] : null)
                   : ($file['category_id'] !== null ? (int) $file['category_id'] : null);

        if ($name === '') {
            ApiResponse::error('Le nom ne peut pas être vide', 422);
        }

        $repo->update((int) $file['id'], $name, $catId);
        ApiResponse::json($this->formatFile($repo->findById((int) $file['id'])));
    }

    /**
     * DELETE /api/v1/files/{id}
     */
    public function destroy(array $params): void
    {
        $this->authenticate();
        $repo = new FileRepository();
        $file = $repo->findById($this->intParam($params, 'id'));

        if (!$file) {
            ApiResponse::error('Fichier introuvable', 404);
        }

        if (is_file($file['storage_path'])) {
            unlink($file['storage_path']);
        }

        (new ShareRepository())->deleteByFileId((int) $file['id']);
        $repo->delete((int) $file['id']);

        ApiResponse::noContent();
    }

    private function formatFile(array $file): array
    {
        return [
            'id'            => (int) $file['id'],
            'name'          => $file['original_name'],
            'extension'     => $file['extension'],
            'mime_type'     => $file['mime_type'],
            'size_bytes'    => (int) $file['size_bytes'],
            'size_human'    => View::formatBytes((int) $file['size_bytes']),
            'sha256'        => $file['sha256'],
            'category_id'   => $file['category_id'] !== null ? (int) $file['category_id'] : null,
            'category_name' => $file['category_name'] ?? null,
            'uploaded_by'   => $file['uploader_email'] ?? null,
            'created_at'    => $file['created_at'],
        ];
    }
}
