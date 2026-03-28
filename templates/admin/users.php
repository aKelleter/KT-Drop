<?php
use App\Core\View;

$userList     = is_array($users ?? null) ? $users : [];
$allowedRoles = is_array($allowedRoles ?? null) ? $allowedRoles : ['admin', 'editor'];
$currentUser  = $user ?? [];
$currentId    = (int) ($currentUser['id'] ?? 0);

$totalUsers  = count($userList);
$totalAdmins = count(array_filter($userList, fn($u) => $u['role'] === 'admin'));

$formatDate = static function (?string $date): string {
    if (empty($date)) return '';
    $ts = strtotime($date);
    return $ts !== false ? date('d/m/Y H:i', $ts) : $date;
};

$roleLabel = static function (string $role): string {
    return match ($role) {
        'admin'  => 'Admin',
        'editor' => 'Éditeur',
        default  => ucfirst($role),
    };
};
?>

<?php if (!empty($flash)): ?>
    <div class="alert mt-4 text-center alert-<?= View::e($flash['type'] ?? 'info') ?> shadow-sm">
        <?= View::e($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mt-4 mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h4 mb-0 app-section-title">Gestion des utilisateurs</h1>
        <p class="small app-muted mb-0 mt-1">
            <?= $totalUsers ?> utilisateur<?= $totalUsers > 1 ? 's' : '' ?> &middot;
            <?= $totalAdmins ?> admin<?= $totalAdmins > 1 ? 's' : '' ?>
        </p>   
    </div>
    
    <button
        class="btn btn-orange btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#createUserModal"
    >
        <i class="bi bi-person-plus me-1"></i> Ajouter un utilisateur
    </button>
    <a href="?action=admin" class="btn btn-outline-orange btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="card app-card shadow-soft">
    <div class="card-body p-0">
        <?php if (empty($userList)): ?>
            <p class="text-center app-muted py-5 mb-0">Aucun utilisateur.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Fichiers</th>
                            <th>Créé le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userList as $u): ?>
                            <?php $uid = (int) $u['id']; ?>
                            <tr>
                                <td class="align-middle">
                                    <?= View::e($u['email']) ?>
                                    <?php if ($uid === $currentId): ?>
                                        <span class="badge admin-badge-you ms-1">vous</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">
                                    <span class="badge admin-role-badge admin-role-<?= View::e($u['role']) ?>">
                                        <?= View::e($roleLabel($u['role'])) ?>
                                    </span>
                                </td>
                                <td class="align-middle app-muted small">
                                    <?= (int) ($u['file_count'] ?? 0) ?>
                                </td>
                                <td class="align-middle app-muted small">
                                    <?= View::e($formatDate($u['created_at'] ?? '')) ?>
                                </td>
                                <td class="align-middle text-end">
                                    <button
                                        class="btn btn-sm btn-outline-orange me-1"
                                        title="Modifier"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-id="<?= $uid ?>"
                                        data-email="<?= View::e($u['email']) ?>"
                                        data-role="<?= View::e($u['role']) ?>"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <?php if ($uid !== $currentId): ?>
                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            title="Supprimer"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteUserModal"
                                            data-id="<?= $uid ?>"
                                            data-email="<?= View::e($u['email']) ?>"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-danger" disabled title="Impossible de supprimer votre propre compte">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal : Créer un utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content app-modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="createUserModalLabel">
                    <i class="bi bi-person-plus me-2 text-orange"></i>Ajouter un utilisateur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="?action=admin_user_create">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create-email" class="form-label small fw-semibold">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="create-email"
                            name="email"
                            required
                            autocomplete="off"
                        >
                    </div>
                    <div class="mb-3">
                        <label for="create-password" class="form-label small fw-semibold">
                            Mot de passe <span class="app-muted fw-normal">(min. 8 caractères)</span>
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="create-password"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                    </div>
                    <div class="mb-3">
                        <label for="create-password-confirm" class="form-label small fw-semibold">
                            Confirmer le mot de passe
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="create-password-confirm"
                            name="password_confirm"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <div class="invalid-feedback">Les mots de passe ne correspondent pas.</div>
                    </div>
                    <div class="mb-1">
                        <label for="create-role" class="form-label small fw-semibold">Rôle</label>
                        <select class="form-select" id="create-role" name="role">
                            <?php foreach ($allowedRoles as $r): ?>
                                <option value="<?= View::e($r) ?>" <?= $r === 'editor' ? 'selected' : '' ?>>
                                    <?= View::e($roleLabel($r)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 admin-modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-orange btn-sm">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Modifier un utilisateur -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content app-modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="bi bi-pencil me-2 text-orange"></i>Modifier l'utilisateur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="?action=admin_user_update">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-email" class="form-label small fw-semibold">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="edit-email"
                            name="email"
                            required
                            autocomplete="off"
                        >
                    </div>
                    <div class="mb-3">
                        <label for="edit-password" class="form-label small fw-semibold">
                            Nouveau mot de passe <span class="app-muted fw-normal">(laisser vide pour ne pas modifier)</span>
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="edit-password"
                            name="password"
                            minlength="8"
                            autocomplete="new-password"
                        >
                    </div>
                    <div class="mb-3">
                        <label for="edit-password-confirm" class="form-label small fw-semibold">
                            Confirmer le nouveau mot de passe
                        </label>
                        <input
                            type="password"
                            class="form-control"
                            id="edit-password-confirm"
                            name="password_confirm"
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <div class="invalid-feedback">Les mots de passe ne correspondent pas.</div>
                    </div>
                    <div class="mb-1">
                        <label for="edit-role" class="form-label small fw-semibold">Rôle</label>
                        <select class="form-select" id="edit-role" name="role">
                            <?php foreach ($allowedRoles as $r): ?>
                                <option value="<?= View::e($r) ?>">
                                    <?= View::e($roleLabel($r)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 admin-modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-orange btn-sm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Confirmer suppression -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content app-modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="bi bi-trash me-2 text-danger"></i>Supprimer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="?action=admin_user_delete">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">
                <input type="hidden" name="id" id="delete-id">
                <div class="modal-body">
                    <p class="mb-0">
                        Supprimer l'utilisateur<br>
                        <strong id="delete-email" class="text-break"></strong> ?
                    </p>
                    <p class="small app-muted mt-2 mb-0">
                        Ses fichiers déposés ne seront pas supprimés.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0 admin-modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    function checkPasswordsMatch(pwdId, confirmId) {
        const pwd     = document.getElementById(pwdId);
        const confirm = document.getElementById(confirmId);
        if (!pwd || !confirm) return true;
        const match = pwd.value === '' || pwd.value === confirm.value;
        confirm.classList.toggle('is-invalid', !match);
        return match;
    }

    /* --- Modal Créer --- */
    const createForm = document.querySelector('#createUserModal form');
    if (createForm) {
        const pwd     = document.getElementById('create-password');
        const confirm = document.getElementById('create-password-confirm');

        [pwd, confirm].forEach(el => el?.addEventListener('input', () => {
            checkPasswordsMatch('create-password', 'create-password-confirm');
        }));

        createForm.addEventListener('submit', function (e) {
            if (!checkPasswordsMatch('create-password', 'create-password-confirm')) {
                e.preventDefault();
            }
        });
    }

    /* --- Modal Modifier --- */
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('edit-id').value    = btn.dataset.id;
            document.getElementById('edit-email').value = btn.dataset.email;
            document.getElementById('edit-password').value = '';
            const confirm = document.getElementById('edit-password-confirm');
            if (confirm) { confirm.value = ''; confirm.classList.remove('is-invalid'); }

            const roleSelect = document.getElementById('edit-role');
            for (const opt of roleSelect.options) {
                opt.selected = opt.value === btn.dataset.role;
            }
        });

        const editForm = editModal.querySelector('form');
        const pwd      = document.getElementById('edit-password');
        const confirm  = document.getElementById('edit-password-confirm');

        [pwd, confirm].forEach(el => el?.addEventListener('input', () => {
            checkPasswordsMatch('edit-password', 'edit-password-confirm');
        }));

        editForm?.addEventListener('submit', function (e) {
            if (!checkPasswordsMatch('edit-password', 'edit-password-confirm')) {
                e.preventDefault();
            }
        });
    }

    /* --- Modal Supprimer --- */
    const deleteModal = document.getElementById('deleteUserModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('delete-id').value       = btn.dataset.id;
            document.getElementById('delete-email').textContent = btn.dataset.email;
        });
    }
}());
</script>
