<?php
use App\Core\View;

$fileList        = is_array($files      ?? null) ? $files      : [];
$searchTerm      = trim((string) ($search     ?? ''));
$activeCategoryId = ($categoryId ?? null) !== null ? (int) $categoryId : null;
$categoryList    = is_array($categories ?? null) ? $categories : [];
$totalFileCount  = (int) ($totalFiles ?? count($fileList));
$startItemCount  = (int) ($startItem  ?? 0);
$endItemCount    = (int) ($endItem    ?? 0);

$hasFilter = $searchTerm !== '' || $activeCategoryId !== null;
?>

<?php if (!empty($flash)): ?>
    <div class="alert text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card app-card shadow-soft">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h1 class="h4 mb-1 app-section-title">Liste de tous les fichiers</h1>
                        <div class="small app-muted">
                            Vue légère avec téléchargement direct
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge app-badge"><?= $totalFileCount ?> fichier(s)</span>

                        <a href="?action=dashboard" class="btn btn-outline-orange">
                            Retour dashboard
                        </a>
                    </div>
                </div>

                <form method="get" action="" class="search-form mb-3">
                    <input type="hidden" name="action" value="files-list">

                    <div class="row g-2">
                        <div class="col-md-8">
                            <input
                                type="text"
                                name="search"
                                class="form-control app-input"
                                placeholder="Rechercher un fichier, une extension ou un utilisateur..."
                                value="<?= View::e($searchTerm) ?>"
                            >
                        </div>

                        <?php if (!empty($categoryList)): ?>
                            <div class="col-md-4">
                                <select name="category" class="form-select app-input">
                                    <option value="">Toutes les catégories</option>
                                    <?php foreach ($categoryList as $cat): ?>
                                        <option
                                            value="<?= (int) $cat['id'] ?>"
                                            <?= $activeCategoryId === (int) $cat['id'] ? 'selected' : '' ?>
                                        >
                                            <?= View::e((string) $cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-orange flex-fill">
                                Rechercher
                            </button>

                            <?php if ($hasFilter): ?>
                                <a href="?action=files-list" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <?php if (!empty($categoryList)): ?>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <a
                            href="?action=files-list&search=<?= urlencode($searchTerm) ?>"
                            class="badge text-decoration-none <?= $activeCategoryId === null ? 'bg-dark' : 'app-badge' ?>"
                            style="font-size:.8rem;"
                        >
                            Tous
                        </a>
                        <?php foreach ($categoryList as $cat): ?>
                            <?php
                            $cid    = (int) $cat['id'];
                            $cname  = (string) $cat['name'];
                            $ccolor = (string) ($cat['color'] ?? '#6c757d');
                            $isActive = $activeCategoryId === $cid;
                            ?>
                            <a
                                href="?action=files-list&search=<?= urlencode($searchTerm) ?>&category=<?= $cid ?>"
                                class="badge text-decoration-none"
                                style="font-size:.8rem;background-color:<?= View::e($ccolor) ?>;opacity:<?= $isActive ? '1' : '0.6' ?>;"
                            >
                                <?= View::e($cname) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($fileList)): ?>
                    <div class="app-muted">
                        <?php if ($hasFilter): ?>
                            Aucun résultat pour les filtres appliqués.
                        <?php else: ?>
                            Aucun fichier pour le moment.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <ul class="simple-page-file-list">
                        <?php foreach ($fileList as $file): ?>
                            <?php
                            $fileId       = (int)    ($file['id']            ?? 0);
                            $originalName = (string) ($file['original_name'] ?? '');
                            $extension    = (string) ($file['extension']     ?? '');
                            $catName      = (string) ($file['category_name'] ?? '');
                            $catColor     = (string) ($file['category_color'] ?? '#6c757d');
                            $iconMeta = View::fileIconMeta($extension);
                            ?>
                            <li class="simple-page-file-item">
                                <div class="simple-page-file-main">
                                    <span
                                        class="file-icon-wrap <?= View::e($iconMeta['class']) ?>"
                                        title="<?= View::e($iconMeta['label']) ?>"
                                        aria-hidden="true"
                                    >
                                        <i class="<?= View::e($iconMeta['icon']) ?>"></i>
                                    </span>

                                    <span
                                        class="simple-page-file-name"
                                        title="<?= View::e($originalName) ?>"
                                    >
                                        <?= View::e($originalName) ?>
                                    </span>

                                    <span class="simple-page-file-ext">
                                        <?= View::e($extension !== '' ? strtoupper($extension) : 'FILE') ?>
                                    </span>

                                    <?php if ($catName !== ''): ?>
                                        <span
                                            class="badge ms-1"
                                            style="font-size:.7rem;background-color:<?= View::e($catColor) ?>;"
                                        >
                                            <?= View::e($catName) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="file-card-actions" style="margin-top:0;padding-top:0;justify-content:flex-end;">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-card-action btn-card-preview js-preview-btn"
                                        title="Aperçu"
                                        aria-label="Aperçu <?= View::e($originalName) ?>"
                                        data-preview-url="?action=preview&id=<?= $fileId ?>"
                                        data-download-url="?action=download&id=<?= $fileId ?>"
                                        data-file-name="<?= View::e($originalName) ?>"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <a
                                        href="?action=download&id=<?= $fileId ?>"
                                        class="btn btn-sm btn-card-action btn-card-download"
                                        title="Télécharger"
                                        aria-label="Télécharger <?= View::e($originalName) ?>"
                                    >
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content preview-modal-content">
            <div class="modal-header preview-modal-header">
                <h5 class="modal-title preview-modal-title" id="filePreviewModalLabel">
                    Aperçu du fichier
                </h5>
                <button
                    type="button"
                    class="btn-close preview-modal-close"
                    data-bs-dismiss="modal"
                    aria-label="Fermer"
                ></button>
            </div>

            <div class="modal-body">
                <div id="file-preview-content" class="file-preview-content">
                    <div class="file-preview-empty">
                        Sélectionnez un fichier à prévisualiser.
                    </div>
                </div>
            </div>

            <div class="modal-footer preview-modal-footer">
                <a href="#" id="file-preview-download-link" class="btn btn-orange btn-modal-action d-none">
                    Télécharger
                </a>
                <button
                    type="button"
                    class="btn btn-outline-orange btn-modal-action"
                    data-bs-dismiss="modal"
                >
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>
