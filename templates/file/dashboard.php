<?php use App\Core\View; ?>

<?php if (!empty($flash)): ?>
    <div class="alert text-center alert-<?= View::e($flash['type']) ?> shadow-sm">
        <?= View::e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card app-card shadow-soft h-100">
            <div class="card-body">
                <h2 class="h5 mb-3 app-section-title">Uploader un fichier</h2>

                <form method="post" action="?action=upload" enctype="multipart/form-data" id="upload-form">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

                   <div id="dropzone" class="dropzone mb-3 rounded-4 p-4 text-center">
                        <p class="mb-2 fw-semibold app-dropzone-title">Glisse ton fichier ici</p>
                        <p class="small app-muted mb-2">ou clique pour le sélectionner</p>
                        <p class="small app-muted mb-3">
                            Taille maximale autorisée : <strong><?= View::e($maxUploadSize ?? '') ?></strong>
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
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card app-card shadow-soft">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h2 class="h5 mb-0 app-section-title">Fichiers déposés</h2>
                    <span class="badge app-badge"><?= count($files) ?> fichier(s)</span>
                </div>

                <form method="get" action="" class="search-form mb-3">
                    <input type="hidden" name="action" value="dashboard">

                    <div class="row g-2">
                        <div class="col-md-8">
                            <input
                                type="text"
                                name="search"
                                class="form-control app-input"
                                placeholder="Rechercher un fichier, une extension ou un utilisateur..."
                                value="<?= View::e($search ?? '') ?>"
                            >
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-orange flex-fill">
                                Rechercher
                            </button>

                            <?php if (!empty($search)): ?>
                                <a href="?action=dashboard" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <?php if (empty($files)): ?>
                    <div class="app-muted">
                        <?php if (!empty($search)): ?>
                            Aucun résultat pour la recherche <strong><?= View::e($search) ?></strong>.
                        <?php else: ?>
                            Aucun fichier pour le moment.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div>
                        <table class="table app-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Taille</th>
                                    <th>Date</th>
                                    <th>Par</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <div class="file-name" title="<?= View::e($file['original_name']) ?>">
                                            <?= View::e($file['original_name']) ?>
                                        </div>
                                    </td>
                                    <td><?= View::e($file['extension']) ?></td>
                                    <td><?= View::e(View::formatBytes((int) $file['size_bytes'])) ?></td>
                                    <td><?= View::e($file['created_at']) ?></td>
                                    <td>
                                        <div class="uploader-email" title="<?= View::e($file['uploader_email']) ?>">
                                            <?= View::e($file['uploader_email']) ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="file-actions">
                                            <a
                                                href="?action=download&id=<?= (int) $file['id'] ?>"
                                                class="btn btn-sm btn-download"
                                            >
                                                Télécharger
                                            </a>

                                            <form method="post" action="?action=delete" onsubmit="return confirm('Supprimer ce fichier ?');">
                                                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-delete">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
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