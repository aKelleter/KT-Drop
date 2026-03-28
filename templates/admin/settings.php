<?php
use App\Core\View;

$extensions = is_array($extensions ?? null) ? $extensions : [];
sort($extensions);
?>

<?php if (!empty($flash)): ?>
    <div class="alert mt-4 text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mt-4 mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h4 mb-0 app-section-title">Paramètres</h1>
        <p class="small app-muted mb-0 mt-1">Configuration générale de l'application.</p>
    </div>
    <a href="?action=admin" class="btn btn-outline-orange btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<form method="post" action="?action=admin_settings_save" id="settings-form">
    <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">

    <div class="card app-card shadow-soft mb-3">
        <div class="card-body p-4">

            <div class="d-flex align-items-start justify-content-between gap-3 mb-3 flex-wrap">
                <div>
                    <h2 class="h6 app-section-title mb-0">Extensions de fichiers autorisées</h2>
                    <p class="small app-muted mt-1 mb-0">
                        Seuls les fichiers avec ces extensions pourront être déposés.
                    </p>
                </div>
                <span class="badge admin-role-badge admin-role-admin" id="ext-count-badge">
                    <?= count($extensions) ?> extension<?= count($extensions) > 1 ? 's' : '' ?>
                </span>
            </div>

            <!-- Zone des pills -->
            <div class="settings-ext-tags mb-3" id="ext-tags">
                <?php foreach ($extensions as $ext): ?>
                    <span class="settings-ext-pill" data-ext="<?= View::e($ext) ?>">
                        <span class="settings-ext-pill-label"><?= View::e(strtoupper($ext)) ?></span>
                        <button type="button" class="settings-ext-pill-remove" aria-label="Supprimer <?= View::e($ext) ?>">
                            <i class="bi bi-x"></i>
                        </button>
                        <input type="hidden" name="extensions[]" value="<?= View::e($ext) ?>">
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Ajouter une extension -->
            <div class="d-flex gap-2 align-items-center" style="max-width: 360px;">
                <input
                    type="text"
                    id="new-ext-input"
                    class="form-control app-input"
                    placeholder="ex : svg, heic, webm…"
                    maxlength="10"
                    autocomplete="off"
                    spellcheck="false"
                >
                <button type="button" class="btn btn-outline-orange btn-sm flex-shrink-0" id="add-ext-btn">
                    <i class="bi bi-plus-lg me-1"></i>Ajouter
                </button>
            </div>
            <p class="small app-muted mt-2 mb-0" id="ext-error" style="display:none; color: var(--app-red);"></p>

        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-orange">
            <i class="bi bi-floppy me-1"></i>Enregistrer
        </button>
    </div>
</form>

<!-- Maintenance -->
<div class="card app-card shadow-soft mt-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-tools me-1"></i> Maintenance
    </div>
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="fw-semibold small mb-1">Mise à jour de la base de données</div>
                <p class="small app-muted mb-0">
                    Exécute les migrations idempotentes (<code>init_db.php</code>) : crée les tables manquantes,
                    ajoute les colonnes absentes. Sans effet sur les données existantes.
                </p>
            </div>
            <form method="post" action="?action=admin_run_migration"
                  onsubmit="return confirm('Lancer la migration de la base de données ?')">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-database-gear me-1"></i> Lancer la migration
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const tagsContainer = document.getElementById('ext-tags');
    const input         = document.getElementById('new-ext-input');
    const addBtn        = document.getElementById('add-ext-btn');
    const errorEl       = document.getElementById('ext-error');
    const countBadge    = document.getElementById('ext-count-badge');

    function updateCount() {
        const n = tagsContainer.querySelectorAll('.settings-ext-pill').length;
        countBadge.textContent = n + ' extension' + (n > 1 ? 's' : '');
    }

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
        setTimeout(() => { errorEl.style.display = 'none'; }, 3000);
    }

    function existingExts() {
        return Array.from(tagsContainer.querySelectorAll('.settings-ext-pill'))
            .map(el => el.dataset.ext.toLowerCase());
    }

    function addExtension(raw) {
        const ext = raw.replace(/[^a-z0-9]/gi, '').toLowerCase().trim();

        if (ext === '') {
            showError('Extension invalide.');
            return;
        }
        if (ext.length > 10) {
            showError('Extension trop longue (max. 10 caractères).');
            return;
        }
        if (existingExts().includes(ext)) {
            showError('Cette extension est déjà dans la liste.');
            return;
        }

        const pill = document.createElement('span');
        pill.className = 'settings-ext-pill';
        pill.dataset.ext = ext;
        pill.innerHTML =
            '<span class="settings-ext-pill-label">' + ext.toUpperCase() + '</span>' +
            '<button type="button" class="settings-ext-pill-remove" aria-label="Supprimer ' + ext + '">' +
            '<i class="bi bi-x"></i></button>' +
            '<input type="hidden" name="extensions[]" value="' + ext + '">';

        pill.querySelector('.settings-ext-pill-remove').addEventListener('click', () => removePill(pill));
        tagsContainer.appendChild(pill);
        updateCount();

        input.value = '';
        input.focus();
    }

    function removePill(pill) {
        const remaining = tagsContainer.querySelectorAll('.settings-ext-pill').length;
        if (remaining <= 1) {
            showError('Vous devez conserver au moins une extension.');
            return;
        }
        pill.remove();
        updateCount();
    }

    // Bind remove on existing pills
    tagsContainer.querySelectorAll('.settings-ext-pill-remove').forEach(btn => {
        btn.addEventListener('click', () => removePill(btn.closest('.settings-ext-pill')));
    });

    addBtn.addEventListener('click', () => addExtension(input.value));

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addExtension(input.value);
        }
    });
}());
</script>
