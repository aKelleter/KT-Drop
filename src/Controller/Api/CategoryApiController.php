<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Core\ApiResponse;
use App\Repository\CategoryRepository;

final class CategoryApiController extends BaseApiController
{
    /**
     * GET /api/v1/categories
     */
    public function index(array $params): void
    {
        $this->authenticate();
        $categories = (new CategoryRepository())->findAll();
        ApiResponse::json(array_map([$this, 'formatCategory'], $categories));
    }

    /**
     * GET /api/v1/categories/{id}
     */
    public function show(array $params): void
    {
        $this->authenticate();
        $cat = (new CategoryRepository())->findById($this->intParam($params, 'id'));

        if (!$cat) {
            ApiResponse::error('Catégorie introuvable', 404);
        }

        ApiResponse::json($this->formatCategory($cat));
    }

    /**
     * POST /api/v1/categories
     * JSON: { "name": "...", "color": "#xxxxxx" }
     * Admin only.
     */
    public function store(array $params): void
    {
        $user = $this->authenticate();
        $this->requireAdmin($user);

        $body  = $this->body();
        $name  = trim((string) ($body['name'] ?? ''));
        $color = trim((string) ($body['color'] ?? '#6c757d'));

        if ($name === '') {
            ApiResponse::error('Le nom est obligatoire', 422);
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            ApiResponse::error('La couleur doit être au format #RRGGBB', 422);
        }

        $repo = new CategoryRepository();

        if ($repo->nameExists($name)) {
            ApiResponse::error('Une catégorie avec ce nom existe déjà', 409);
        }

        $repo->create($name, $color);
        $id  = (int) \App\Core\Database::connection()->lastInsertId();
        $cat = $repo->findById($id);

        ApiResponse::json($this->formatCategory($cat), 201);
    }

    /**
     * PATCH /api/v1/categories/{id}
     * JSON: { "name": "...", "color": "#xxxxxx" }
     * Admin only.
     */
    public function update(array $params): void
    {
        $user = $this->authenticate();
        $this->requireAdmin($user);

        $repo = new CategoryRepository();
        $id   = $this->intParam($params, 'id');
        $cat  = $repo->findById($id);

        if (!$cat) {
            ApiResponse::error('Catégorie introuvable', 404);
        }

        $body  = $this->body();
        $name  = trim((string) ($body['name'] ?? $cat['name']));
        $color = trim((string) ($body['color'] ?? $cat['color']));

        if ($name === '') {
            ApiResponse::error('Le nom est obligatoire', 422);
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            ApiResponse::error('La couleur doit être au format #RRGGBB', 422);
        }

        if ($repo->nameExists($name, $id)) {
            ApiResponse::error('Une catégorie avec ce nom existe déjà', 409);
        }

        $repo->update($id, $name, $color);
        ApiResponse::json($this->formatCategory($repo->findById($id)));
    }

    /**
     * DELETE /api/v1/categories/{id}
     * Admin only.
     */
    public function destroy(array $params): void
    {
        $user = $this->authenticate();
        $this->requireAdmin($user);

        $repo = new CategoryRepository();
        $cat  = $repo->findById($this->intParam($params, 'id'));

        if (!$cat) {
            ApiResponse::error('Catégorie introuvable', 404);
        }

        $repo->delete((int) $cat['id']);
        ApiResponse::noContent();
    }

    private function formatCategory(array $cat): array
    {
        return [
            'id'         => (int) $cat['id'],
            'name'       => $cat['name'],
            'color'      => $cat['color'],
            'created_at' => $cat['created_at'],
        ];
    }
}
