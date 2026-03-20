<?php
use App\Core\View;

$shareData   = $share ?? null;
$errorMsg    = $error ?? null;
$shareToken  = $token ?? '';

$formatDate = static function (?string $date): string {
    if (empty($date)) return '';
    $ts = strtotime($date);
    return $ts !== false ? date('d/m/Y à H:i', $ts) : $date;
};
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card app-card shadow-soft">
            <div class="card-body text-center py-5">

                <?php if ($errorMsg !== null): ?>

                    <div class="mb-4">
                        <i class="bi bi-link-45deg" style="font-size: 3rem; color: var(--color-orange);"></i>
                    </div>

                    <h1 class="h4 mb-2">Lien de partage</h1>
                    <p class="app-muted"><?= View::e($errorMsg) ?></p>

                <?php else: ?>

                    <?php
                    $extension = (string) ($shareData['extension'] ?? '');
                    $iconMeta  = View::fileIconMeta($extension);
                    ?>

                    <div class="mb-4">
                        <span class="file-icon-wrap <?= View::e($iconMeta['class']) ?>" style="font-size: 3rem; width: auto; height: auto; padding: 0;">
                            <i class="<?= View::e($iconMeta['icon']) ?>"></i>
                        </span>
                    </div>

                    <h1 class="h5 mb-1 fw-semibold" title="<?= View::e((string) ($shareData['original_name'] ?? '')) ?>">
                        <?= View::e((string) ($shareData['original_name'] ?? 'Fichier')) ?>
                    </h1>

                    <p class="small app-muted mb-1">
                        <?= View::e(View::formatBytes((int) ($shareData['size_bytes'] ?? 0))) ?>
                        <?php if ($extension !== ''): ?>
                            &middot; <?= View::e(strtoupper($extension)) ?>
                        <?php endif; ?>
                    </p>

                    <p class="small app-muted mb-4">
                        Lien valide jusqu'au <?= View::e($formatDate((string) ($shareData['expires_at'] ?? ''))) ?>
                    </p>

                    <a
                        href="?action=share_dl&token=<?= View::e($shareToken) ?>"
                        class="btn btn-orange fw-semibold px-4"
                    >
                        <i class="bi bi-download me-2"></i>Télécharger
                    </a>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
