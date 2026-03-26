<?php
use App\Core\View;

$global         = is_array($global         ?? null) ? $global         : [];
$extensions     = is_array($extensions     ?? null) ? $extensions     : [];
$activity       = is_array($activity       ?? null) ? $activity       : [];
$uploaders      = is_array($uploaders      ?? null) ? $uploaders      : [];
$categoryStats  = is_array($categoryStats  ?? null) ? $categoryStats  : [];
$sizeStats      = is_array($sizeStats      ?? null) ? $sizeStats      : [];

$totalFiles   = (int)   ($global['total_files']   ?? 0);
$totalSize    = (int)   ($global['total_size']    ?? 0);
$avgSize      = (float) ($global['avg_size']      ?? 0);
$activeShares = (int)   ($global['active_shares'] ?? 0);
$totalUsers   = (int)   ($global['total_users']   ?? 0);
$adminCount   = (int)   ($global['admin_count']   ?? 0);
$editorCount  = (int)   ($global['editor_count']  ?? 0);

$maxExtCount  = max(1, ...array_map(fn($r) => (int) $r['file_count'], $extensions ?: [['file_count' => 1]]));
$maxDayCount  = max(1, ...array_map(fn($r) => (int) $r['file_count'], $activity   ?: [['file_count' => 1]]));
$maxUploads   = max(1, ...array_map(fn($r) => (int) $r['file_count'], $uploaders  ?: [['file_count' => 1]]));
?>

<?php if (!empty($flash)): ?>
    <div class="alert text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h4 mb-0 app-section-title">Statistiques</h1>
        <p class="small app-muted mb-0 mt-1">Vue d'ensemble de l'activité et du stockage.</p>
    </div>
    <a href="?action=admin" class="btn btn-outline-orange btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<!-- Chiffres clés -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body stats-kpi-card">
                <div class="stats-kpi-icon">
                    <i class="bi bi-files"></i>
                </div>
                <div class="stats-kpi-value"><?= number_format($totalFiles, 0, ',', ' ') ?></div>
                <div class="stats-kpi-label">Fichier<?= $totalFiles > 1 ? 's' : '' ?> déposé<?= $totalFiles > 1 ? 's' : '' ?></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body stats-kpi-card">
                <div class="stats-kpi-icon">
                    <i class="bi bi-hdd"></i>
                </div>
                <div class="stats-kpi-value"><?= View::e(View::formatBytes($totalSize)) ?></div>
                <div class="stats-kpi-label">Espace occupé</div>
                <?php if ($totalFiles > 0): ?>
                    <div class="stats-kpi-sub">moy. <?= View::e(View::formatBytes((int) $avgSize)) ?> / fichier</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body stats-kpi-card">
                <div class="stats-kpi-icon">
                    <i class="bi bi-share"></i>
                </div>
                <div class="stats-kpi-value"><?= $activeShares ?></div>
                <div class="stats-kpi-label">Lien<?= $activeShares > 1 ? 's' : '' ?> de partage actif<?= $activeShares > 1 ? 's' : '' ?></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body stats-kpi-card">
                <div class="stats-kpi-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stats-kpi-value"><?= $totalUsers ?></div>
                <div class="stats-kpi-label">Utilisateur<?= $totalUsers > 1 ? 's' : '' ?></div>
                <div class="stats-kpi-sub"><?= $adminCount ?> admin · <?= $editorCount ?> éditeur<?= $editorCount > 1 ? 's' : '' ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    <!-- Activité des 30 derniers jours -->
    <div class="col-lg-8">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body">
                <h2 class="h6 app-section-title mb-3">Activité (30 derniers jours)</h2>

                <?php if ($totalFiles === 0): ?>
                    <p class="app-muted small mb-0">Aucun fichier déposé pour le moment.</p>
                <?php else: ?>
                    <div class="stats-activity-chart">
                        <?php foreach ($activity as $day): ?>
                            <?php
                            $count   = (int) $day['file_count'];
                            $pct     = $count > 0 ? max(4, (int) round($count / $maxDayCount * 100)) : 0;
                            $label   = date('d/m', strtotime($day['day']));
                            $title   = $label . ' : ' . $count . ' fichier' . ($count > 1 ? 's' : '');
                            ?>
                            <div class="stats-activity-col" title="<?= View::e($title) ?>">
                                <div class="stats-activity-bar-wrap">
                                    <div
                                        class="stats-activity-bar <?= $count > 0 ? 'stats-activity-bar--active' : '' ?>"
                                        style="height: <?= $pct ?>%"
                                    ></div>
                                </div>
                                <?php if (date('N', strtotime($day['day'])) == 1): ?>
                                    <div class="stats-activity-label"><?= View::e($label) ?></div>
                                <?php else: ?>
                                    <div class="stats-activity-label">&nbsp;</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top utilisateurs -->
    <div class="col-lg-4">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body">
                <h2 class="h6 app-section-title mb-3">Top déposants</h2>

                <?php if (empty($uploaders) || $maxUploads === 0): ?>
                    <p class="app-muted small mb-0">Aucune donnée.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($uploaders as $up): ?>
                            <?php
                            $count = (int) $up['file_count'];
                            $pct   = $count > 0 ? max(4, (int) round($count / $maxUploads * 100)) : 0;
                            ?>
                            <div>
                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                    <span class="small text-truncate fw-semibold" style="max-width: 65%;" title="<?= View::e($up['email']) ?>">
                                        <?= View::e($up['email']) ?>
                                    </span>
                                    <span class="small app-muted">
                                        <?= $count ?> fichier<?= $count > 1 ? 's' : '' ?>
                                        &middot; <?= View::e(View::formatBytes((int) $up['total_size'])) ?>
                                    </span>
                                </div>
                                <div class="stats-hbar-track">
                                    <div class="stats-hbar-fill" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Répartition par catégorie -->
<?php if (!empty($categoryStats)): ?>
<?php
$maxCatCount = max(1, ...array_map(fn($r) => (int) $r['file_count'], $categoryStats));
$totalCatFiles = array_sum(array_map(fn($r) => (int) $r['file_count'], $categoryStats));
?>
<div class="card app-card shadow-soft mb-3">
    <div class="card-body">
        <h2 class="h6 app-section-title mb-3">
            <i class="bi bi-tags me-2"></i>Répartition par catégorie
        </h2>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($categoryStats as $cat): ?>
                <?php
                $count     = (int)    $cat['file_count'];
                $size      = (int)    $cat['total_size'];
                $name      = (string) $cat['name'];
                $color     = (string) $cat['color'];
                $pct       = $count > 0 ? max(2, (int) round($count / $maxCatCount * 100)) : 0;
                $sharePct  = $totalCatFiles > 0 ? round($count / $totalCatFiles * 100, 1) : 0;
                $isNone    = $name === 'Sans catégorie';
                ?>
                <div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!$isNone): ?>
                                <span
                                    class="d-inline-block rounded-circle flex-shrink-0"
                                    style="width:10px;height:10px;background-color:<?= View::e($color) ?>;"
                                ></span>
                            <?php else: ?>
                                <span class="d-inline-block flex-shrink-0" style="width:10px;"></span>
                            <?php endif; ?>
                            <span class="small fw-semibold <?= $isNone ? 'app-muted' : '' ?>">
                                <?= View::e($name) ?>
                            </span>
                        </div>
                        <span class="small app-muted ms-3 text-nowrap">
                            <?= $count ?> fichier<?= $count > 1 ? 's' : '' ?>
                            &middot; <?= View::e(View::formatBytes($size)) ?>
                            &middot; <?= $sharePct ?>%
                        </span>
                    </div>
                    <div class="stats-hbar-track">
                        <div
                            class="stats-hbar-fill"
                            style="width:<?= $pct ?>%;<?= !$isNone ? 'background-color:' . View::e($color) . ';' : '' ?>"
                        ></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Répartition par taille de fichier -->
<?php
$maxSizeCount  = max(1, ...array_map(fn($r) => (int) $r['file_count'], $sizeStats ?: [['file_count' => 1]]));
$totalSizeFiles = array_sum(array_map(fn($r) => (int) $r['file_count'], $sizeStats));
?>
<?php if ($totalSizeFiles > 0): ?>
<div class="card app-card shadow-soft mb-3">
    <div class="card-body">
        <h2 class="h6 app-section-title mb-3">
            <i class="bi bi-layout-wtf me-2"></i>Répartition par taille de fichier
        </h2>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($sizeStats as $range): ?>
                <?php
                $count    = (int)    $range['file_count'];
                $size     = (int)    $range['total_size'];
                $label    = (string) $range['label'];
                $color    = (string) $range['color'];
                $pct      = $count > 0 ? max(2, (int) round($count / $maxSizeCount * 100)) : 0;
                $sharePct = $totalSizeFiles > 0 ? round($count / $totalSizeFiles * 100, 1) : 0;
                ?>
                <div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span
                                class="d-inline-block rounded-circle flex-shrink-0"
                                style="width:10px;height:10px;background-color:<?= View::e($color) ?>;"
                            ></span>
                            <span class="small fw-semibold <?= $count === 0 ? 'app-muted' : '' ?>">
                                <?= View::e($label) ?>
                            </span>
                        </div>
                        <span class="small app-muted ms-3 text-nowrap">
                            <?php if ($count > 0): ?>
                                <?= $count ?> fichier<?= $count > 1 ? 's' : '' ?>
                                &middot; <?= View::e(View::formatBytes($size)) ?>
                                &middot; <?= $sharePct ?>%
                            <?php else: ?>
                                aucun fichier
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="stats-hbar-track">
                        <div
                            class="stats-hbar-fill"
                            style="width:<?= $pct ?>%;<?= $count > 0 ? 'background-color:' . View::e($color) . ';' : '' ?>"
                        ></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Répartition par extension -->
<?php if (!empty($extensions)): ?>
<div class="card app-card shadow-soft">
    <div class="card-body">
        <h2 class="h6 app-section-title mb-3">Répartition par type de fichier</h2>
        <div class="row g-3">
            <?php foreach ($extensions as $ext): ?>
                <?php
                $count = (int) $ext['file_count'];
                $pct   = $count > 0 ? max(4, (int) round($count / $maxExtCount * 100)) : 0;
                $iconMeta = View::fileIconMeta((string) $ext['extension']);
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="file-icon-wrap <?= View::e($iconMeta['class']) ?>" style="width:28px;height:28px;font-size:.8rem;flex-shrink:0;" aria-hidden="true">
                            <i class="<?= View::e($iconMeta['icon']) ?>"></i>
                        </span>
                        <span class="small fw-semibold text-uppercase"><?= View::e($ext['extension']) ?></span>
                        <span class="ms-auto small app-muted"><?= $count ?> · <?= View::e(View::formatBytes((int) $ext['total_size'])) ?></span>
                    </div>
                    <div class="stats-hbar-track">
                        <div class="stats-hbar-fill" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
