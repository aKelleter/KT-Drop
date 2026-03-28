<?php
use App\Core\View;
?>

<?php if (!empty($flash)): ?>
    <div class="alert mt-4 text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= $flash['message'] ?? '' ?>
    </div>
<?php endif; ?>

<?php if (!empty($newToken)): ?>
    <div class="alert alert-success shadow-sm" id="new-token-alert" data-persistent>
        <div class="fw-semibold mb-2"><i class="bi bi-check-circle me-1"></i> Token généré avec succès</div>
        <p class="small mb-2">Copiez-le maintenant — il ne sera <strong>plus affiché</strong> après avoir quitté cette page.</p>
        <div class="input-group">
            <input type="text" id="new-token-value" class="form-control form-control-sm font-monospace"
                   value="<?= View::e($newToken) ?>" readonly>
            <button class="btn btn-success btn-sm" type="button" onclick="copyToken()">
                <i class="bi bi-clipboard" id="copy-icon"></i> Copier
            </button>
        </div>
    </div>
    <script>
    function copyToken() {
        const input = document.getElementById('new-token-value');
        navigator.clipboard.writeText(input.value).then(() => {
            const icon = document.getElementById('copy-icon');
            icon.className = 'bi bi-clipboard-check';
            document.querySelector('#new-token-alert .btn').textContent = ' Copié !';
            document.querySelector('#new-token-alert .btn').prepend(icon);
        });
    }
    </script>
<?php endif; ?>

<div class="mb-4 mt-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1 app-section-title">Tokens API</h1>
        <p class="small app-muted mb-0">Gérez les clés d'accès à l'API REST.</p>
    </div>
    <a href="?action=admin" class="btn btn-outline-orange btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<!-- Créer un token -->
<div class="card app-card shadow-soft mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-plus-circle me-1"></i> Nouveau token
    </div>
    <div class="card-body">
        <form method="post" action="?action=admin_api_token_create" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

            <div class="col-sm-5">
                <label class="form-label small fw-semibold">Nom du token</label>
                <input type="text" name="name" class="form-control form-control-sm"
                       placeholder="ex : Intégration Zapier" required maxlength="80">
            </div>

            <div class="col-sm-5">
                <label class="form-label small fw-semibold">Utilisateur associé</label>
                <select name="user_id" class="form-select form-select-sm" required>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= View::e($u['id']) ?>">
                            <?= View::e($u['email']) ?> (<?= View::e($u['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-sm-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-app w-100">
                    <i class="bi bi-key me-1"></i> Générer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des tokens -->
<div class="card app-card shadow-soft">
    <div class="card-header fw-semibold">
        <i class="bi bi-list-ul me-1"></i> Tokens existants
        <span class="badge bg-secondary ms-2"><?= count($tokens) ?></span>
    </div>

    <?php if (empty($tokens)): ?>
        <div class="card-body text-center app-muted py-4">
            <i class="bi bi-key fs-2 d-block mb-2"></i>
            Aucun token généré pour l'instant.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Utilisateur</th>
                        <th>Token</th>
                        <th>Dernière utilisation</th>
                        <th>Créé le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $t): ?>
                        <tr>
                            <td class="fw-semibold"><?= View::e($t['name']) ?></td>
                            <td>
                                <?= View::e($t['email']) ?>
                                <span class="badge bg-<?= $t['role'] === 'admin' ? 'danger' : 'secondary' ?> ms-1">
                                    <?= View::e($t['role']) ?>
                                </span>
                            </td>
                            <td>
                                <code class="text-muted" style="font-size:.75rem;">
                                    <?= View::e(substr($t['token'], 0, 8)) ?>…<?= View::e(substr($t['token'], -4)) ?>
                                </code>
                            </td>
                            <td class="app-muted">
                                <?= $t['last_used_at'] ? View::e($t['last_used_at']) : '<span class="text-muted">Jamais</span>' ?>
                            </td>
                            <td class="app-muted"><?= View::e($t['created_at']) ?></td>
                            <td class="text-end">
                                <form method="post" action="?action=admin_api_token_revoke"
                                      onsubmit="return confirm('Révoquer ce token ?')">
                                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= View::e($t['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
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

<!-- Documentation rapide -->
<div class="card app-card shadow-soft mt-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-book me-1"></i> Utilisation
    </div>
    <div class="card-body">
        <p class="small mb-2">Ajoutez le header suivant à chaque requête :</p>
        <pre class="bg-light rounded p-2 mb-3" style="font-size:.8rem;">Authorization: Bearer &lt;votre-token&gt;</pre>

        <p class="small fw-semibold mb-1">Endpoints disponibles</p>
        <table class="table table-sm table-bordered small mb-3">
            <thead class="table-light">
                <tr><th>Méthode</th><th>Endpoint</th><th>Paramètres</th><th>Description</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge bg-primary">GET</span></td>
                    <td><code>/api/v1/files</code></td>
                    <td><code>page</code> <code>search</code> <code>category</code></td>
                    <td>Liste paginée (20 / page)</td>
                </tr>
                <tr>
                    <td><span class="badge bg-primary">GET</span></td>
                    <td><code>/api/v1/files/all</code></td>
                    <td><code>search</code> <code>category</code></td>
                    <td>Tous les fichiers sans pagination</td>
                </tr>
                <tr>
                    <td><span class="badge bg-primary">GET</span></td>
                    <td><code>/api/v1/files/{id}</code></td>
                    <td>—</td>
                    <td>Détail d'un fichier</td>
                </tr>
                <tr>
                    <td><span class="badge bg-success">POST</span></td>
                    <td><code>/api/v1/files</code></td>
                    <td><code>file</code> <code>category_id</code></td>
                    <td>Upload (multipart/form-data)</td>
                </tr>
                <tr>
                    <td><span class="badge bg-warning text-dark">PATCH</span></td>
                    <td><code>/api/v1/files/{id}</code></td>
                    <td><code>name</code> <code>category_id</code></td>
                    <td>Modifier nom / catégorie</td>
                </tr>
                <tr>
                    <td><span class="badge bg-danger">DELETE</span></td>
                    <td><code>/api/v1/files/{id}</code></td>
                    <td>—</td>
                    <td>Supprimer un fichier</td>
                </tr>
                <tr>
                    <td><span class="badge bg-primary">GET</span></td>
                    <td><code>/api/v1/categories</code></td>
                    <td>—</td>
                    <td>Liste des catégories</td>
                </tr>
                <tr>
                    <td><span class="badge bg-success">POST</span></td>
                    <td><code>/api/v1/categories</code></td>
                    <td><code>name</code> <code>color</code></td>
                    <td>Créer une catégorie (admin)</td>
                </tr>
                <tr>
                    <td><span class="badge bg-warning text-dark">PATCH</span></td>
                    <td><code>/api/v1/categories/{id}</code></td>
                    <td><code>name</code> <code>color</code></td>
                    <td>Modifier une catégorie (admin)</td>
                </tr>
                <tr>
                    <td><span class="badge bg-danger">DELETE</span></td>
                    <td><code>/api/v1/categories/{id}</code></td>
                    <td>—</td>
                    <td>Supprimer une catégorie (admin)</td>
                </tr>
                <tr>
                    <td><span class="badge bg-primary">GET</span></td>
                    <td><code>/api/v1/stats</code></td>
                    <td>—</td>
                    <td>Statistiques globales (admin)</td>
                </tr>
            </tbody>
        </table>

        <p class="small fw-semibold mb-2">Exemples curl</p>
        <pre class="bg-light rounded p-3 mb-0" style="font-size:.78rem;overflow-x:auto;">
<span class="text-muted"># Page 2 de la liste</span>
curl -H "Authorization: Bearer &lt;token&gt;" "/api/v1/files?page=2"

<span class="text-muted"># Recherche par nom</span>
curl -H "Authorization: Bearer &lt;token&gt;" "/api/v1/files?search=rapport"

<span class="text-muted"># Filtrer par catégorie (id=3)</span>
curl -H "Authorization: Bearer &lt;token&gt;" "/api/v1/files?category=3"

<span class="text-muted"># Combiner les filtres</span>
curl -H "Authorization: Bearer &lt;token&gt;" "/api/v1/files?search=doc&amp;category=3&amp;page=1"

<span class="text-muted"># Tous les fichiers d'une catégorie (sans pagination)</span>
curl -H "Authorization: Bearer &lt;token&gt;" "/api/v1/files/all?category=3"

<span class="text-muted"># Modifier le nom et la catégorie d'un fichier</span>
curl -X PATCH -H "Authorization: Bearer &lt;token&gt;" \
     -H "Content-Type: application/json" \
     -d '{"name":"archive.zip","category_id":2}' \
     "/api/v1/files/42"</pre>
    </div>
</div>
