<?php
declare(strict_types=1);

namespace App\Controller;

use App\Config\Config;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Core\View;
use App\Repository\SettingsRepository;
use App\Repository\ShareRepository;
use App\Repository\StatsRepository;
use App\Repository\UserRepository;
use App\Service\FileStorageService;

final class AdminController
{
    private const ALLOWED_ROLES = ['admin', 'editor'];

    private function requireAdmin(): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Accès refusé.');
        }
    }

    public function dashboard(): void
    {
        $this->requireAdmin();

        View::render('admin/dashboard', [
            'user'  => Auth::user(),
            'flash' => Flash::get(),
        ]);
    }

    public function settings(): void
    {
        $this->requireAdmin();

        $repo       = new SettingsRepository();
        $rawExts    = $repo->get('allowed_extensions');
        $extensions = $rawExts !== ''
            ? array_values(array_filter(array_map('trim', explode(',', strtolower($rawExts)))))
            : (new FileStorageService())->getAllowedExtensions();

        View::render('admin/settings', [
            'user'       => Auth::user(),
            'flash'      => Flash::get(),
            'csrf'       => Csrf::token(),
            'extensions' => $extensions,
        ]);
    }

    public function saveSettings(): void
    {
        $this->requireAdmin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin_settings');
        }

        $raw = $_POST['extensions'] ?? [];

        if (!is_array($raw)) {
            $raw = [];
        }

        $extensions = array_values(array_unique(array_filter(
            array_map(
                fn($e) => preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $e))),
                $raw
            ),
            fn($e) => $e !== '' && strlen($e) <= 10
        )));

        if (empty($extensions)) {
            Flash::set('danger', 'La liste des extensions ne peut pas être vide.');
            Response::redirect('?action=admin_settings');
        }

        (new SettingsRepository())->set('allowed_extensions', implode(',', $extensions));

        Flash::set('success', 'Paramètres enregistrés.');
        Response::redirect('?action=admin_settings');
    }

    public function stats(): void
    {
        $this->requireAdmin();

        $repo = new StatsRepository();

        View::render('admin/stats', [
            'user'       => Auth::user(),
            'flash'      => Flash::get(),
            'global'     => $repo->findGlobal(),
            'extensions' => $repo->findTopExtensions(8),
            'activity'   => $repo->findUploadsPerDay(30),
            'uploaders'  => $repo->findTopUploaders(5),
        ]);
    }

    public function shares(): void
    {
        $this->requireAdmin();

        View::render('admin/shares', [
            'user'   => Auth::user(),
            'shares' => (new ShareRepository())->findAllActive(),
            'csrf'   => Csrf::token(),
            'flash'  => Flash::get(),
            'appUrl' => rtrim((string) Config::get('APP_URL', ''), '/') . '/',
        ]);
    }

    public function users(): void
    {
        $this->requireAdmin();

        $repo = new UserRepository();

        View::render('admin/users', [
            'user'         => Auth::user(),
            'users'        => $repo->findAll(),
            'csrf'         => Csrf::token(),
            'flash'        => Flash::get(),
            'allowedRoles' => self::ALLOWED_ROLES,
        ]);
    }

    public function createUser(): void
    {
        $this->requireAdmin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin_users');
        }

        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $role            = $_POST['role'] ?? 'editor';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('danger', 'Adresse email invalide.');
            Response::redirect('?action=admin_users');
        }

        if (strlen($password) < 8) {
            Flash::set('danger', 'Le mot de passe doit contenir au moins 8 caractères.');
            Response::redirect('?action=admin_users');
        }

        if ($password !== $passwordConfirm) {
            Flash::set('danger', 'Les mots de passe ne correspondent pas.');
            Response::redirect('?action=admin_users');
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $role = 'editor';
        }

        $repo = new UserRepository();

        if ($repo->emailExists($email)) {
            Flash::set('danger', 'Cette adresse email est déjà utilisée.');
            Response::redirect('?action=admin_users');
        }

        $repo->create([
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $role,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        Flash::set('success', 'Utilisateur créé.');
        Response::redirect('?action=admin_users');
    }

    public function updateUser(): void
    {
        $this->requireAdmin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin_users');
        }

        $id              = (int) ($_POST['id'] ?? 0);
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $role            = $_POST['role'] ?? 'editor';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('danger', 'Adresse email invalide.');
            Response::redirect('?action=admin_users');
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $role = 'editor';
        }

        $repo     = new UserRepository();
        $existing = $repo->findById($id);

        if (!$existing) {
            Flash::set('danger', 'Utilisateur introuvable.');
            Response::redirect('?action=admin_users');
        }

        if ($repo->emailExists($email, $id)) {
            Flash::set('danger', 'Cette adresse email est déjà utilisée.');
            Response::redirect('?action=admin_users');
        }

        if ($existing['role'] === 'admin' && $role !== 'admin' && $repo->countAdmins() <= 1) {
            Flash::set('danger', 'Impossible de retirer le rôle admin au dernier administrateur.');
            Response::redirect('?action=admin_users');
        }

        $data = ['email' => $email, 'role' => $role];

        if ($password !== '') {
            if (strlen($password) < 8) {
                Flash::set('danger', 'Le mot de passe doit contenir au moins 8 caractères.');
                Response::redirect('?action=admin_users');
            }

            if ($password !== $passwordConfirm) {
                Flash::set('danger', 'Les mots de passe ne correspondent pas.');
                Response::redirect('?action=admin_users');
            }

            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $repo->update($id, $data);

        if ($id === Auth::id()) {
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['role']  = $role;
        }

        Flash::set('success', 'Utilisateur mis à jour.');
        Response::redirect('?action=admin_users');
    }

    public function deleteUser(): void
    {
        $this->requireAdmin();

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin_users');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id === Auth::id()) {
            Flash::set('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            Response::redirect('?action=admin_users');
        }

        $repo = new UserRepository();
        $user = $repo->findById($id);

        if (!$user) {
            Flash::set('danger', 'Utilisateur introuvable.');
            Response::redirect('?action=admin_users');
        }

        if ($user['role'] === 'admin' && $repo->countAdmins() <= 1) {
            Flash::set('danger', 'Impossible de supprimer le dernier administrateur.');
            Response::redirect('?action=admin_users');
        }

        $repo->delete($id);

        Flash::set('success', 'Utilisateur supprimé.');
        Response::redirect('?action=admin_users');
    }
}
