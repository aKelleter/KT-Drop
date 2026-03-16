<?php
use App\Core\View;

$fileList       = is_array($files ?? null) ? $files : [];
$searchTerm     = trim((string) ($search ?? ''));
$totalFileCount = (int) ($totalFiles ?? count($fileList));
$startItemCount = (int) ($startItem ?? 0);
$endItemCount   = (int) ($endItem ?? 0);
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
                        <h1 class="h4 mb-1 app-section-title">Liste simple des fichiers</h1>
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

                <form method="get" action="" class="search-form mb-4">
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

                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-orange flex-fill">
                                Rechercher
                            </button>

                            <?php if ($searchTerm !== ''): ?>
                                <a href="?action=files-list&page=1" class="btn btn-outline-secondary">
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
                    <ul class="simple-page-file-list">
                        <?php foreach ($fileList as $file): ?>
                            <?php
                            $fileId       = (int) ($file['id'] ?? 0);
                            $originalName = (string) ($file['original_name'] ?? '');
                            $extension    = (string) ($file['extension'] ?? '');
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
                                </div>

                                <a
                                    href="?action=download&id=<?= $fileId ?>"
                                    class="btn btn-sm btn-outline-orange simple-page-file-download"
                                >
                                    Télécharger
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
           
            </div>
        </div>
    </div>
</div>