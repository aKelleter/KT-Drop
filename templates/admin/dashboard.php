<?php
use App\Core\View;
?>

<?php if (!empty($flash)): ?>
    <div class="alert text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="mb-4">
    <h1 class="h4 mb-1 app-section-title">Administration</h1>
    <p class="small app-muted mb-0">Gérez les paramètres et les ressources de l'application.</p>
</div>

<div class="row g-3">

    <div class="col-sm-6 col-lg-4">
        <a href="?action=admin_users" class="admin-module-card card app-card shadow-soft text-decoration-none h-100">
            <div class="card-body d-flex align-items-start gap-3 p-4">
                <div class="admin-module-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="fw-semibold admin-module-title">Utilisateurs</div>
                    <div class="small app-muted mt-1">
                        Créer, modifier et supprimer les comptes. Gérer les rôles d'accès.
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-4">
        <a href="?action=admin_shares" class="admin-module-card card app-card shadow-soft text-decoration-none h-100">
            <div class="card-body d-flex align-items-start gap-3 p-4">
                <div class="admin-module-icon">
                    <i class="bi bi-share"></i>
                </div>
                <div>
                    <div class="fw-semibold admin-module-title">Partages actifs</div>
                    <div class="small app-muted mt-1">
                        Consulter et révoquer l'ensemble des liens de partage en cours.
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-4">
        <a href="?action=admin_stats" class="admin-module-card card app-card shadow-soft text-decoration-none h-100">
            <div class="card-body d-flex align-items-start gap-3 p-4">
                <div class="admin-module-icon">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <div>
                    <div class="fw-semibold admin-module-title">Statistiques</div>
                    <div class="small app-muted mt-1">
                        Espace utilisé, nombre de fichiers, activité récente.
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-4">
        <a href="?action=admin_categories" class="admin-module-card card app-card shadow-soft text-decoration-none h-100">
            <div class="card-body d-flex align-items-start gap-3 p-4">
                <div class="admin-module-icon">
                    <i class="bi bi-tags"></i>
                </div>
                <div>
                    <div class="fw-semibold admin-module-title">Catégories</div>
                    <div class="small app-muted mt-1">
                        Créer et gérer les catégories pour organiser les fichiers.
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-4">
        <a href="?action=admin_settings" class="admin-module-card card app-card shadow-soft text-decoration-none h-100">
            <div class="card-body d-flex align-items-start gap-3 p-4">
                <div class="admin-module-icon">
                    <i class="bi bi-sliders"></i>
                </div>
                <div>
                    <div class="fw-semibold admin-module-title">Paramètres</div>
                    <div class="small app-muted mt-1">
                        Types de fichiers autorisés, taille maximale, quota par utilisateur.
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-4">
        <a href="?action=admin_api_tokens" class="admin-module-card card app-card shadow-soft text-decoration-none h-100">
            <div class="card-body d-flex align-items-start gap-3 p-4">
                <div class="admin-module-icon">
                    <i class="bi bi-key"></i>
                </div>
                <div>
                    <div class="fw-semibold admin-module-title">Tokens API</div>
                    <div class="small app-muted mt-1">
                        Générer et révoquer les clés d'accès à l'API REST.
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>
