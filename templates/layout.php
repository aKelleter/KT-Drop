<?php

use App\Config\Config;
use App\Core\Auth;
use App\Core\View;

$appName = Config::get('APP_NAME', 'KT-Drop V2');
$appVersion = Config::get('APP_VERSION', '0.0.0');
$appUpd = Config::get('APP_UPD', '0.0.0');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= View::e((string) $appName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= View::asset('img/favicon.png') ?>" type="image/png">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
</head>
<body class="app-body">

<div class="app-shell">
    <nav class="navbar navbar-expand-lg app-navbar app-navbar-fixed">
    <div class="container">
        <a class="navbar-brand app-brand d-flex align-items-center gap-2" href="?action=dashboard">
            <span class="app-brand-icon">
                <img 
                    src="<?= View::asset('img/favicon.png') ?>" 
                    alt="KT-Drop" 
                    height="32"
                    class="app-logo"
                >
            </span>
            <span><?= View::e((string) $appName) ?></span>
        </a>

        <?php if (Auth::check()): ?>
            <div class="d-flex align-items-center gap-3">
                <span class="small app-user-email">
                    <?= View::e(Auth::user()['email'] ?? '') ?>
                </span>
                <a href="?action=dashboard" class="btn btn-outline-orange btn-sm">
                    <i class="bi bi-house me-1"></i>Fichiers
                </a>
                <?php if (Auth::isAdmin()): ?>
                    <a href="?action=admin" class="btn btn-outline-orange btn-sm">
                        <i class="bi bi-shield-lock me-1"></i>Administration
                    </a>
                <?php endif; ?>
                <a href="?action=logout" class="btn btn-outline-orange btn-sm">
                    Déconnexion
                </a>
            </div>
        <?php endif; ?>
    </div>
</nav>

    <main class="app-main py-4 py-md-5">
        <div class="container">
            <?php require $viewPath; ?>
        </div>
    </main>

    <footer class="app-footer">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><?= View::e((string) $appName) ?></span>
            <span>Version <?= View::e((string) $appVersion) ?> - <?= View::e((string) $appUpd) ?></span>
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="<?= View::asset('js/app.js') ?>"></script>
</body>
</html>