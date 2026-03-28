<?php
use App\Core\View;

$categoryList = is_array($categories ?? null) ? $categories : [];
?>

<?php if (!empty($flash)): ?>
    <div class="alert mt-4 text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mt-4 mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1 app-section-title">Catégories</h1>
        <p class="small app-muted mb-0">Gérez les catégories pour organiser les fichiers.</p>
    </div>
    <a href="?action=admin" class="btn btn-outline-orange btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card app-card shadow-soft">
            <div class="card-body">
                <h2 class="h6 mb-3 app-section-title">Nouvelle catégorie</h2>
                <form method="post" action="?action=admin_category_create">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">

                    <div class="mb-3">
                        <label for="cat-name" class="form-label small fw-semibold">Nom</label>
                        <input
                            type="text"
                            id="cat-name"
                            name="name"
                            class="form-control app-input"
                            placeholder="Ex : Documents, Images..."
                            maxlength="64"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="cat-color" class="form-label small fw-semibold">Couleur</label>
                        <div class="d-flex align-items-center gap-2">
                            <input
                                type="color"
                                id="cat-color"
                                name="color"
                                class="form-control form-control-color"
                                value="#f97316"
                                style="width: 48px; height: 38px;"
                            >
                            <span class="small app-muted">Choisissez une couleur d'identification</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-orange w-100">
                        <i class="bi bi-plus-lg me-1"></i>Créer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card app-card shadow-soft">
            <div class="card-body">
                <h2 class="h6 mb-3 app-section-title">
                    Liste des catégories
                    <span class="badge app-badge ms-2"><?= count($categoryList) ?></span>
                </h2>

                <?php if (empty($categoryList)): ?>
                    <p class="app-muted small">Aucune catégorie créée pour le moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="small">
                                <tr>
                                    <th>Couleur</th>
                                    <th>Nom</th>
                                    <th>Créée le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryList as $cat): ?>
                                    <?php
                                    $catId    = (int) $cat['id'];
                                    $catName  = (string) $cat['name'];
                                    $catColor = (string) ($cat['color'] ?? '#6c757d');
                                    $catDate  = (string) ($cat['created_at'] ?? '');
                                    $ts = strtotime($catDate);
                                    $catDateFmt = $ts !== false ? date('d/m/Y', $ts) : $catDate;
                                    ?>
                                    <tr>
                                        <td>
                                            <span
                                                class="d-inline-block rounded"
                                                style="width: 20px; height: 20px; background-color: <?= View::e($catColor) ?>;"
                                                title="<?= View::e($catColor) ?>"
                                            ></span>
                                        </td>
                                        <td class="fw-semibold"><?= View::e($catName) ?></td>
                                        <td class="small app-muted"><?= View::e($catDateFmt) ?></td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editCategoryModal"
                                                data-cat-id="<?= $catId ?>"
                                                data-cat-name="<?= View::e($catName) ?>"
                                                data-cat-color="<?= View::e($catColor) ?>"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <form method="post" action="?action=admin_category_delete" class="d-inline" onsubmit="return confirm('Supprimer « <?= View::e($catName) ?> » ? Les fichiers associés seront déclassés.');">
                                                <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                                                <input type="hidden" name="id" value="<?= $catId ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash3"></i>
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
    </div>
</div>

<!-- Modal édition catégorie -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content preview-modal-content">
            <div class="modal-header preview-modal-header">
                <h5 class="modal-title preview-modal-title" id="editCategoryModalLabel">
                    <i class="bi bi-pencil me-2"></i>Modifier la catégorie
                </h5>
                <button type="button" class="btn-close preview-modal-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="post" action="?action=admin_category_update">
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                    <input type="hidden" name="id" id="edit-cat-id">

                    <div class="mb-3">
                        <label for="edit-cat-name" class="form-label small fw-semibold">Nom</label>
                        <input
                            type="text"
                            id="edit-cat-name"
                            name="name"
                            class="form-control app-input"
                            maxlength="64"
                            required
                        >
                    </div>

                    <div class="mb-1">
                        <label for="edit-cat-color" class="form-label small fw-semibold">Couleur</label>
                        <input
                            type="color"
                            id="edit-cat-color"
                            name="color"
                            class="form-control form-control-color"
                            style="width: 48px; height: 38px;"
                        >
                    </div>
                </div>
                <div class="modal-footer preview-modal-footer">
                    <button type="button" class="btn btn-outline-orange btn-modal-action" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-orange btn-modal-action">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editCategoryModal');
    if (!editModal) return;

    editModal.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('edit-cat-id').value    = btn.dataset.catId;
        document.getElementById('edit-cat-name').value  = btn.dataset.catName;
        document.getElementById('edit-cat-color').value = btn.dataset.catColor;
    });
});
</script>
