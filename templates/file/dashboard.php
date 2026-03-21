<?php
use App\Core\View;

$fileList          = is_array($files ?? null) ? $files : [];
$searchTerm        = trim((string) ($search ?? ''));
$totalFileCount    = (int) ($totalFiles ?? count($fileList));
$currentPage       = max(1, (int) ($page ?? 1));
$totalPageCount    = max(1, (int) ($totalPages ?? 1));
$startItemCount    = (int) ($startItem ?? 0);
$endItemCount      = (int) ($endItem ?? 0);
$maxUploadSizeText  = (string) ($maxUploadSize ?? '');
$sharesByFileId     = is_array($sharesByFileId ?? null) ? $sharesByFileId : [];
$appUrl             = rtrim((string) ($appUrl ?? ''), '/') . '/';

$allowedExts = array_map('strtoupper', is_array($allowedExtensions ?? null) ? $allowedExtensions : []);
$popoverHtml = implode(' ', array_map(
    static fn(string $ext): string => '<span class="ext-pill">' . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') . '</span>',
    $allowedExts
));
$popoverContent = htmlspecialchars($popoverHtml, ENT_QUOTES, 'UTF-8');

$startPage = max(1, $currentPage - 2);
$endPage   = min($totalPageCount, $currentPage + 2);

$dashboardUrl = static fn (int $pageNumber): string
    => '?action=dashboard&search=' . urlencode($searchTerm) . '&page=' . $pageNumber;

$formatDate = static function (?string $date): string {
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date('d/m/Y H:i', $timestamp)
        : $date;
};
?>

<?php if (!empty($flash)): ?>
    <div class="alert text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body">
                <h2 class="h5 mb-3 app-section-title">Uploader un fichier</h2>

                <form method="post" action="?action=upload" enctype="multipart/form-data" id="upload-form">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">

                    <div id="dropzone" class="dropzone mb-3 rounded-4 p-4 text-center">
                        <p class="mb-2 fw-semibold app-dropzone-title">Glissez votre fichier ici</p>
                        <p class="small app-muted mb-2">ou cliquez pour le sélectionner</p>
                        <p class="small app-muted mb-3">
                            Taille maximale autorisée :
                            <strong><?= View::e($maxUploadSizeText) ?></strong>
                            <button
                                type="button"
                                class="btn p-0 border-0 align-baseline ms-1"
                                data-bs-toggle="popover"
                                data-bs-trigger="hover focus"
                                data-bs-placement="bottom"
                                data-bs-html="true"
                                data-bs-title="Extensions autorisées"
                                data-bs-content="<?= $popoverContent ?>"
                                aria-label="Voir les extensions autorisées"
                            >
                                <i class="bi bi-info-circle app-muted"></i>
                            </button>
                        </p>

                        <input type="file" name="file" id="file-input" class="d-none" required>

                        <button type="button" class="btn btn-outline-orange" id="pick-file-btn">
                            Choisir un fichier
                        </button>

                        <div id="selected-file" class="small text-orange mt-3"></div>
                    </div>

                    <button type="submit" class="btn btn-orange w-100 fw-semibold">
                        Envoyer
                    </button>

                    <div id="upload-progress-wrapper" class="upload-progress-wrapper d-none mt-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div
                                id="upload-spinner"
                                class="spinner-border spinner-border-sm text-warning"
                                role="status"
                                aria-hidden="true"
                            ></div>
                            <span id="upload-status-text" class="small app-muted">Upload en cours...</span>
                        </div>

                        <div
                            class="progress upload-progress-bar-wrap"
                            role="progressbar"
                            aria-label="Progression de l'upload"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                id="upload-progress-bar"
                                class="progress-bar upload-progress-bar"
                                style="width: 0%;"
                            >
                                0%
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card app-card shadow-soft">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h2 class="h5 mb-0 app-section-title">Fichiers déposés</h2>

                    <div class="d-flex align-items-center gap-2">
                        <div class="dropdown">
                            <button
                                class="btn btn-outline-orange btn-sm dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                Vues
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end app-dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="?action=dashboard">
                                        <i class="bi bi-grid-3x3-gap me-2"></i>
                                        Vue cartes
                                    </a>
                                </li>

                                <li>
                                    <button
                                        type="button"
                                        class="dropdown-item"
                                        data-bs-toggle="modal"
                                        data-bs-target="#simpleFilesListModal"
                                    >
                                        <i class="bi bi-list-ul me-2"></i>
                                        Liste rapide
                                    </button>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="?action=files-list">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        Liste tous les fichiers
                                    </a>
                                </li>

                                <li><hr class="dropdown-divider"></li>

                                <li>
                                    <a class="dropdown-item" href="?action=shares">
                                        <i class="bi bi-share me-2"></i>
                                        Partages actifs
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <span class="badge app-badge"><?= $totalFileCount ?> fichier(s)</span>
                    </div>
                </div>

                <form method="get" action="" class="search-form mb-3">
                    <input type="hidden" name="action" value="dashboard">
                    <input type="hidden" name="page" value="1">

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

                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-orange flex-fill">
                                Rechercher
                            </button>

                            <?php if ($searchTerm !== ''): ?>
                                <a href="?action=dashboard&page=1" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <?php if (empty($fileList)): ?>
                    <div class="app-muted">
                        <?php if ($searchTerm !== ''): ?>
                            Aucun résultat pour la recherche <strong><?= View::e($searchTerm) ?></strong>.
                        <?php else: ?>
                            Aucun fichier pour le moment.
                        <?php endif; ?>
                    </div>
                <?php else: ?>

                    <div class="files-grid">
                        <?php foreach ($fileList as $file): ?>
                            <?php
                            $fileId        = (int) ($file['id'] ?? 0);
                            $originalName  = (string) ($file['original_name'] ?? '');
                            $extension     = (string) ($file['extension'] ?? '');
                            $sizeBytes     = (int) ($file['size_bytes'] ?? 0);
                            $createdAt     = (string) ($file['created_at'] ?? '');
                            $uploaderEmail = (string) ($file['uploader_email'] ?? '');
                            $iconMeta = View::fileIconMeta($extension);
                            ?>
                            <article class="file-card h-100">
                               <div class="file-card-header">
                                    <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                                        <span
                                            class="file-icon-wrap <?= View::e($iconMeta['class']) ?>"
                                            title="<?= View::e($iconMeta['label']) ?>"
                                            aria-hidden="true"
                                        >
                                            <i class="<?= View::e($iconMeta['icon']) ?>"></i>
                                        </span>

                                        <div class="file-card-name" title="<?= View::e($originalName) ?>">
                                            <?= View::e($originalName) ?>
                                        </div>
                                    </div>

                                    <span class="file-card-type">
                                        <?= View::e($extension !== '' ? strtoupper($extension) : 'FILE') ?>
                                    </span>
                                </div>

                                <div class="file-card-meta">
                                    <div>
                                        <span class="meta-label">Taille</span>
                                        <span><?= View::e(View::formatBytes($sizeBytes)) ?></span>
                                    </div>

                                    <div>
                                        <span class="meta-label">Date</span>
                                        <span><?= View::e($formatDate($createdAt)) ?></span>
                                    </div>

                                    <div>
                                        <span class="meta-label">Par</span>
                                        <span class="uploader-email-mobile" title="<?= View::e($uploaderEmail) ?>">
                                            <?= View::e($uploaderEmail) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="file-card-actions">
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

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-card-action btn-card-share js-share-btn"
                                        title="Partager"
                                        aria-label="Partager <?= View::e($originalName) ?>"
                                        data-file-id="<?= $fileId ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#shareModal"
                                    >
                                        <i class="bi bi-share"></i>
                                    </button>

                                    <form
                                        method="post"
                                        action="?action=delete"
                                        onsubmit="return confirm('Supprimer ce fichier ?');"
                                    >
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                                        <input type="hidden" name="id" value="<?= $fileId ?>">

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-card-action btn-card-delete"
                                            title="Supprimer"
                                            aria-label="Supprimer <?= View::e($originalName) ?>"
                                        >
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>

                            </article>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
                <?php if ($totalFileCount > 0): ?>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <div class="small app-muted">
                            Affichage de <?= $startItemCount ?> à <?= $endItemCount ?>
                            sur <?= $totalFileCount ?> fichier(s)
                        </div>

                        <?php if ($totalPageCount > 1): ?>
                            <nav aria-label="Pagination des fichiers">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= View::e($dashboardUrl(max(1, $currentPage - 1))) ?>">
                                            Précédent
                                        </a>
                                    </li>

                                    <?php if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= View::e($dashboardUrl(1)) ?>">1</a>
                                        </li>

                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">…</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= View::e($dashboardUrl($i)) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($endPage < $totalPageCount): ?>
                                        <?php if ($endPage < $totalPageCount - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">…</span>
                                            </li>
                                        <?php endif; ?>

                                        <li class="page-item">
                                            <a class="page-link" href="<?= View::e($dashboardUrl($totalPageCount)) ?>">
                                                <?= $totalPageCount ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <li class="page-item <?= $currentPage >= $totalPageCount ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= View::e($dashboardUrl(min($totalPageCount, $currentPage + 1))) ?>">
                                            Suivant
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$formatDate = static function (?string $date): string {
    if (empty($date)) return '';
    $ts = strtotime($date);
    return $ts !== false ? date('d/m/Y à H:i', $ts) : $date;
};

foreach ($fileList as $file):
    $fid  = (int) ($file['id'] ?? 0);
    $fname = (string) ($file['original_name'] ?? '');
    $fileShares = $sharesByFileId[$fid] ?? [];
?>
<div class="d-none" id="share-content-<?= $fid ?>">
    <p class="share-modal-file-name fw-semibold mb-3"><?= View::e($fname) ?></p>

    <?php if (!empty($fileShares)): ?>
        <p class="small app-muted mb-2">Liens actifs :</p>
        <?php foreach ($fileShares as $s): ?>
            <?php $shareUrl = $appUrl . '?action=share&token=' . urlencode((string) $s['token']); ?>
            <div class="share-link-row mb-2">
                <div class="input-group input-group-sm">
                    <input
                        type="text"
                        class="form-control app-input font-monospace"
                        value="<?= View::e($shareUrl) ?>"
                        readonly
                        aria-label="Lien de partage"
                    >
                    <button
                        class="btn btn-outline-secondary js-copy-btn"
                        type="button"
                        data-copy="<?= View::e($shareUrl) ?>"
                        title="Copier"
                    >
                        <i class="bi bi-copy"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="small app-muted">Expire le <?= View::e($formatDate((string) $s['expires_at'])) ?></span>
                    <form method="post" action="?action=share_revoke" class="d-inline">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                        <input type="hidden" name="token" value="<?= View::e((string) $s['token']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                            <i class="bi bi-x-lg me-1"></i>Révoquer
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <hr class="my-3">
    <?php else: ?>
        <p class="small app-muted mb-3">Aucun lien actif pour ce fichier.</p>
    <?php endif; ?>

    <p class="small fw-semibold mb-2">Créer un nouveau lien :</p>
    <form method="post" action="?action=share_create">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
        <input type="hidden" name="file_id" value="<?= $fid ?>">
        <div class="d-flex gap-2">
            <select name="ttl_hours" class="form-select form-select-sm app-input">
                <option value="1">1 heure</option>
                <option value="24" selected>24 heures</option>
                <option value="168">7 jours</option>
                <option value="720">30 jours</option>
            </select>
            <button type="submit" class="btn btn-orange btn-sm text-nowrap">
                <i class="bi bi-link-45deg me-1"></i>Créer
            </button>
        </div>
    </form>
</div>
<?php endforeach; ?>

<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content preview-modal-content">
            <div class="modal-header preview-modal-header">
                <h5 class="modal-title preview-modal-title" id="shareModalLabel">
                    <i class="bi bi-share me-2"></i>Partager un fichier
                </h5>
                <button type="button" class="btn-close preview-modal-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="share-modal-body">
                <p class="app-muted">Chargement…</p>
            </div>
            <div class="modal-footer preview-modal-footer">
                <button type="button" class="btn btn-outline-orange btn-modal-action" data-bs-dismiss="modal">
                    Fermer
                </button>
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

<div class="modal fade" id="simpleFilesListModal" tabindex="-1" aria-labelledby="simpleFilesListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content preview-modal-content">
            <div class="modal-header preview-modal-header">
                <h5 class="modal-title preview-modal-title" id="simpleFilesListModalLabel">
                    Liste rapide des fichiers de la page actuelle
                </h5>

                <button
                    type="button"
                    class="btn-close preview-modal-close"
                    data-bs-dismiss="modal"
                    aria-label="Fermer"
                ></button>
            </div>

            <div class="modal-body">
                <?php if (empty($fileList)): ?>
                    <div class="app-muted">Aucun fichier à afficher.</div>
                <?php else: ?>
                    <ul class="simple-file-list">
                        <?php foreach ($fileList as $file): ?>
                            <?php
                            $fileId       = (int) ($file['id'] ?? 0);
                            $originalName = (string) ($file['original_name'] ?? '');
                            $extension    = (string) ($file['extension'] ?? '');
                            $iconMeta = View::fileIconMeta($extension);
                            ?>
                            <li class="simple-file-item">
                                <div class="simple-file-main">
                                    <span
                                        class="file-icon-wrap <?= View::e($iconMeta['class']) ?>"
                                        title="<?= View::e($iconMeta['label']) ?>"
                                        aria-hidden="true"
                                    >
                                        <i class="<?= View::e($iconMeta['icon']) ?>"></i>
                                    </span>

                                    <span class="simple-file-name" title="<?= View::e($originalName) ?>">
                                        <?= View::e($originalName) ?>
                                    </span>

                                    <span class="simple-file-ext">
                                        <?= View::e($extension !== '' ? strtoupper($extension) : 'FILE') ?>
                                    </span>
                                </div>

                                <a
                                    href="?action=download&id=<?= $fileId ?>"
                                    class="btn btn-sm btn-outline-orange simple-file-download"
                                >
                                    Télécharger
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="modal-footer preview-modal-footer">
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