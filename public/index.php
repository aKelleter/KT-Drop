<?php
declare(strict_types=1);

// ── Pre-flight checks ─────────────────────────────────────────────────────────
$_basePath = dirname(__DIR__);

function _ktdrop_fatal(string $title, string $body, string $hint): never
{
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>KT-Drop — ' . $title . '</title>'
       . '<style>'
       . '*{box-sizing:border-box;margin:0;padding:0}'
       . 'body{min-height:100vh;display:flex;align-items:center;justify-content:center;'
       .      'background:#f5f5f7;font-family:system-ui,sans-serif;color:#1d1d1f;padding:1.5rem}'
       . '.card{background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,.08);'
       .       'padding:2.5rem 2rem;max-width:520px;width:100%}'
       . '.icon{font-size:2.2rem;margin-bottom:1rem}'
       . 'h1{font-size:1.25rem;font-weight:700;margin-bottom:.6rem;color:#1d1d1f}'
       . 'p{font-size:.95rem;line-height:1.55;color:#444}'
       . '.hint{margin-top:1.25rem;padding:.9rem 1rem;background:#fff7f0;'
       .       'border-left:3px solid #ff7a00;border-radius:6px;font-size:.88rem;color:#555}'
       . 'code{background:#f0f0f0;border-radius:4px;padding:.1em .4em;'
       .      'font-family:monospace;font-size:.92em;color:#c0392b}'
       . '</style></head><body>'
       . '<div class="card">'
       . '<div class="icon">⚠️</div>'
       . '<h1>' . $title . '</h1>'
       . '<p>' . $body . '</p>'
       . '<div class="hint">' . $hint . '</div>'
       . '</div></body></html>';
    exit;
}

if (!file_exists($_basePath . '/vendor/autoload.php')) {
    _ktdrop_fatal(
        'Dépendances manquantes',
        'Le répertoire <code>vendor/</code> est introuvable. L\'application ne peut pas démarrer.',
        'Exécutez <code>composer install</code> à la racine du projet, puis rechargez la page.'
    );
}

if (!file_exists($_basePath . '/.env.local')) {
    _ktdrop_fatal(
        'Fichier de configuration manquant',
        'Le fichier <code>.env.local</code> est introuvable. L\'application ne peut pas démarrer.',
        'Copiez <code>.env.local.example</code> en <code>.env.local</code> et ajustez les valeurs selon votre environnement.'
    );
}

require $_basePath . '/config/bootstrap.php';

$_dbRelPath = ltrim((string) ($_ENV['DB_DATABASE'] ?? 'database/app.sqlite'), '/');
if (!file_exists($_basePath . '/' . $_dbRelPath)) {
    _ktdrop_fatal(
        'Base de données introuvable',
        'Le fichier de base de données <code>' . htmlspecialchars($_dbRelPath) . '</code> est introuvable.',
        'Lancez le script d\'initialisation de la base ou vérifiez la valeur de <code>DB_DATABASE</code> dans <code>.env.local</code>.'
    );
}
unset($_basePath, $_dbRelPath);
// ── End pre-flight ────────────────────────────────────────────────────────────

use App\Controller\AdminController;
use App\Controller\AuthController;
use App\Controller\FileController;
use App\Controller\ShareController;
use App\Controller\Api\FileApiController;
use App\Controller\Api\CategoryApiController;
use App\Controller\Api\StatsApiController;
use App\Core\ApiRouter;
use App\Core\Router;

// ── API routing ───────────────────────────────────────────────────────────────
$_requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
// SCRIPT_NAME = /kt-drop/public/index.php → remonter 2 niveaux pour obtenir /kt-drop
$_scriptBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$_apiPath     = $_scriptBase !== '' && str_starts_with($_requestPath, $_scriptBase)
                  ? substr($_requestPath, strlen($_scriptBase))
                  : $_requestPath;

if (str_starts_with($_apiPath, '/api/')) {
    $apiRouter = new ApiRouter();

    $apiRouter->add('GET',    '/api/v1/files',              [FileApiController::class,     'index']);
    $apiRouter->add('GET',    '/api/v1/files/all',          [FileApiController::class,     'all']);
    $apiRouter->add('GET',    '/api/v1/files/{id}',         [FileApiController::class,     'show']);
    $apiRouter->add('POST',   '/api/v1/files',              [FileApiController::class,     'store']);
    $apiRouter->add('PATCH',  '/api/v1/files/{id}',         [FileApiController::class,     'update']);
    $apiRouter->add('DELETE', '/api/v1/files/{id}',         [FileApiController::class,     'destroy']);

    $apiRouter->add('GET',    '/api/v1/categories',         [CategoryApiController::class, 'index']);
    $apiRouter->add('GET',    '/api/v1/categories/{id}',    [CategoryApiController::class, 'show']);
    $apiRouter->add('POST',   '/api/v1/categories',         [CategoryApiController::class, 'store']);
    $apiRouter->add('PATCH',  '/api/v1/categories/{id}',    [CategoryApiController::class, 'update']);
    $apiRouter->add('DELETE', '/api/v1/categories/{id}',    [CategoryApiController::class, 'destroy']);

    $apiRouter->add('GET',    '/api/v1/stats',              [StatsApiController::class,    'index']);

    $apiRouter->dispatch(
        strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
        $_apiPath
    );
    exit;
}
unset($_requestPath, $_scriptBase, $_apiPath);
// ── End API routing ───────────────────────────────────────────────────────────

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
$router->post('file_update', [FileController::class, 'update']);
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

$router->get('admin_categories', [AdminController::class, 'categories']);
$router->post('admin_category_create', [AdminController::class, 'createCategory']);
$router->post('admin_category_update', [AdminController::class, 'updateCategory']);
$router->post('admin_category_delete', [AdminController::class, 'deleteCategory']);

$router->get('admin_api_tokens', [AdminController::class, 'apiTokens']);
$router->post('admin_api_token_create', [AdminController::class, 'createApiToken']);
$router->post('admin_api_token_revoke', [AdminController::class, 'revokeApiToken']);

$router->get('shares', [ShareController::class, 'list']);
$router->post('share_create', [ShareController::class, 'create']);
$router->post('share_revoke', [ShareController::class, 'revoke']);
$router->get('share', [ShareController::class, 'access'], true);
$router->get('share_dl', [ShareController::class, 'download'], true);

$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, $action);