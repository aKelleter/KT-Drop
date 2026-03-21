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

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h4 mb-0 app-section-title">Partages actifs</h1>
        <p class="small app-muted mb-0 mt-1">
            <?= count($shareList) ?> lien<?= count($shareList) > 1 ? 's' : '' ?> en cours de validité
        </p>
    </div>
    <a href="?action=admin" class="btn btn-outline-orange btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="card app-card shadow-soft">
    <div class="card-body p-0">
        <?php if (empty($shareList)): ?>
            <p class="text-center app-muted py-5 mb-0">Aucun lien de partage actif pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover admin-table mb-0">
                    <thead>
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
                            ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="file-icon-wrap <?= View::e($iconMeta['class']) ?> flex-shrink-0" style="width:32px;height:32px;font-size:.9rem;" aria-hidden="true">
                                            <i class="<?= View::e($iconMeta['icon']) ?>"></i>
                                        </span>
                                        <span class="small fw-semibold text-truncate" style="max-width:180px;" title="<?= View::e((string) ($share['original_name'] ?? '')) ?>">
                                            <?= View::e((string) ($share['original_name'] ?? '—')) ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="align-middle">
                                    <div class="input-group input-group-sm" style="max-width: 260px;">
                                        <input
                                            type="text"
                                            class="form-control app-input font-monospace"
                                            value="<?= View::e($shareUrl) ?>"
                                            readonly
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

                                <td class="align-middle small app-muted">
                                    <?= View::e((string) ($share['creator_email'] ?? '—')) ?>
                                </td>

                                <td class="align-middle small">
                                    <?= View::e($formatDate((string) ($share['expires_at'] ?? ''))) ?>
                                </td>

                                <td class="align-middle text-end">
                                    <form method="post" action="?action=share_revoke" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                                        <input type="hidden" name="token" value="<?= View::e((string) $share['token']) ?>">
                                        <input type="hidden" name="_back" value="admin_shares">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Révoquer ce lien de partage ?');"
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
