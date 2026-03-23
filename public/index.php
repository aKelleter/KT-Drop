<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Controller\AdminController;
use App\Controller\AuthController;
use App\Controller\FileController;
use App\Controller\ShareController;
use App\Core\Router;

$router = new Router();

$router->get('login', [AuthController::class, 'login'], true);
$router->post('login_submit', [AuthController::class, 'loginSubmit'], true);

$router->get('logout', [AuthController::class, 'logout']);
$router->get('dashboard', [FileController::class, 'dashboard']);
$router->post('upload', [FileController::class, 'upload']);
$router->post('upload_chunk_init', [FileController::class, 'uploadChunkInit']);
$router->post('upload_chunk', [FileController::class, 'uploadChunk']);
$router->post('upload_chunk_finalize', [FileController::class, 'uploadChunkFinalize']);
$router->get('download', [FileController::class, 'download']);
$router->post('delete', [FileController::class, 'delete']);
$router->get('preview', [FileController::class, 'preview']);
$router->get('files-list', [FileController::class, 'simpleList']);

$router->get('admin', [AdminController::class, 'dashboard']);
$router->get('admin_settings', [AdminController::class, 'settings']);
$router->post('admin_settings_save', [AdminController::class, 'saveSettings']);
$router->get('admin_stats', [AdminController::class, 'stats']);
$router->get('admin_shares', [AdminController::class, 'shares']);
$router->get('admin_users', [AdminController::class, 'users']);
$router->post('admin_user_create', [AdminController::class, 'createUser']);
$router->post('admin_user_update', [AdminController::class, 'updateUser']);
$router->post('admin_user_delete', [AdminController::class, 'deleteUser']);

$router->get('shares', [ShareController::class, 'list']);
$router->post('share_create', [ShareController::class, 'create']);
$router->post('share_revoke', [ShareController::class, 'revoke']);
$router->get('share', [ShareController::class, 'access'], true);
$router->get('share_dl', [ShareController::class, 'download'], true);

$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, $action);