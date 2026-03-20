<?php
use App\Core\View;

$shareList = is_array($shares ?? null) ? $shares : [];
$appUrl    = rtrim((string) ($appUrl ?? ''), '/') . '/';

$formatDate = static function (?string $date): string {
    if (empty($date)) return '';
    $ts = strtotime($date);
    return $ts !== false ? date('d/m/Y H:i', $ts) : $date;
};
?>

<?php if (!empty($flash)): ?>
    <div class="alert text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="card app-card shadow-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="h5 mb-0 app-section-title">
                <i class="bi bi-share me-2"></i>Partages actifs
            </h2>

            <div class="d-flex align-items-center gap-2">
                <span class="badge app-badge"><?= count($shareList) ?> lien(s)</span>
                <a href="?action=dashboard" class="btn btn-outline-orange btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>

        <?php if (empty($shareList)): ?>
            <p class="app-muted">Aucun lien de partage actif pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fichier</th>
                            <th>Lien</th>
                            <th>Créé par</th>
                            <th>Expire le</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shareList as $share): ?>
                            <?php
                            $extension = (string) ($share['extension'] ?? '');
                            $iconMeta  = View::fileIconMeta($extension);
                            $shareUrl  = $appUrl . '?action=share&token=' . urlencode((string) $share['token']);
                            $tokenShort = substr((string) $share['token'], 0, 12) . '…';
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="file-icon-wrap <?= View::e($iconMeta['class']) ?> flex-shrink-0" style="width:32px;height:32px;font-size:.9rem;" aria-hidden="true">
                                            <i class="<?= View::e($iconMeta['icon']) ?>"></i>
                                        </span>
                                        <span class="small fw-semibold" title="<?= View::e((string) ($share['original_name'] ?? '')) ?>">
                                            <?= View::e((string) ($share['original_name'] ?? '—')) ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="input-group input-group-sm" style="max-width: 280px;">
                                        <input
                                            type="text"
                                            class="form-control app-input font-monospace"
                                            value="<?= View::e($shareUrl) ?>"
                                            readonly
                                            title="<?= View::e($shareUrl) ?>"
                                            aria-label="Lien de partage"
                                        >
                                        <button
                                            class="btn btn-outline-secondary js-copy-share-btn"
                                            type="button"
                                            data-copy="<?= View::e($shareUrl) ?>"
                                            title="Copier le lien"
                                        >
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>

                                <td class="small app-muted">
                                    <?= View::e((string) ($share['creator_email'] ?? '—')) ?>
                                </td>

                                <td class="small">
                                    <?= View::e($formatDate((string) ($share['expires_at'] ?? ''))) ?>
                                </td>

                                <td class="text-end">
                                    <form method="post" action="?action=share_revoke" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                                        <input type="hidden" name="token" value="<?= View::e((string) $share['token']) ?>">
                                        <input type="hidden" name="_back" value="shares">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Révoquer ce lien ?');"
                                        >
                                            <i class="bi bi-x-lg me-1"></i>Révoquer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.js-copy-share-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        const text = btn.dataset.copy || '';
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-check2';
                setTimeout(() => { icon.className = 'bi bi-copy'; }, 1500);
            }
        });
    });
});
</script>
