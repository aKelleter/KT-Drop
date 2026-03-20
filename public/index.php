<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

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
$router->get('download', [FileController::class, 'download']);
$router->post('delete', [FileController::class, 'delete']);
$router->get('preview', [FileController::class, 'preview']);
$router->get('files-list', [FileController::class, 'simpleList']);

$router->get('shares', [ShareController::class, 'list']);
$router->post('share_create', [ShareController::class, 'create']);
$router->post('share_revoke', [ShareController::class, 'revoke']);
$router->get('share', [ShareController::class, 'access'], true);
$router->get('share_dl', [ShareController::class, 'download'], true);

$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, $action);