<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Core\View;
use App\Repository\FileRepository;
use App\Repository\ShareRepository;

final class ShareController
{
    private const ALLOWED_TTLS = [1, 24, 168, 720];

    public function create(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=dashboard');
        }

        $fileId   = (int) ($_POST['file_id'] ?? 0);
        $ttlHours = (int) ($_POST['ttl_hours'] ?? 24);

        if (!in_array($ttlHours, self::ALLOWED_TTLS, true)) {
            $ttlHours = 24;
        }

        $file = (new FileRepository())->findById($fileId);

        if (!$file) {
            Flash::set('danger', 'Fichier introuvable.');
            Response::redirect('?action=dashboard');
        }

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlHours * 3600);

        (new ShareRepository())->create([
            'file_id'    => $fileId,
            'token'      => $token,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => Auth::id(),
        ]);

        Flash::set('success', 'Lien de partage créé.');
        Response::redirect('?action=dashboard');
    }

    public function revoke(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=dashboard');
        }

        $token = trim($_POST['token'] ?? '');

        if ($token === '') {
            Flash::set('danger', 'Token invalide.');
            Response::redirect('?action=dashboard');
        }

        (new ShareRepository())->revokeByToken($token);

        Flash::set('success', 'Lien révoqué.');

        $back = trim($_POST['_back'] ?? '');
        Response::redirect(in_array($back, ['shares'], true) ? '?action=' . $back : '?action=dashboard');
    }

    public function list(): void
    {
        $shares = (new ShareRepository())->findAllActive();

        View::render('share/list', [
            'shares' => $shares,
            'csrf'   => Csrf::token(),
            'flash'  => Flash::get(),
            'appUrl' => rtrim((string) \App\Config\Config::get('APP_URL', ''), '/') . '/',
        ]);
    }

    public function access(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            http_response_code(404);
            View::render('share/access', ['error' => 'Lien invalide.', 'share' => null]);
            return;
        }

        $share = (new ShareRepository())->findByToken($token);

        if (!$share) {
            http_response_code(404);
            View::render('share/access', ['error' => 'Ce lien est invalide ou a expiré.', 'share' => null]);
            return;
        }

        if (strtotime((string) $share['expires_at']) < time()) {
            http_response_code(410);
            View::render('share/access', ['error' => 'Ce lien a expiré.', 'share' => null]);
            return;
        }

        if (!is_file((string) $share['storage_path'])) {
            http_response_code(404);
            View::render('share/access', ['error' => 'Le fichier associé à ce lien est introuvable.', 'share' => null]);
            return;
        }

        View::render('share/access', [
            'error' => null,
            'share' => $share,
            'token' => $token,
        ]);
    }

    public function download(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            http_response_code(404);
            echo 'Lien invalide.';
            return;
        }

        $share = (new ShareRepository())->findByToken($token);

        if (!$share) {
            http_response_code(404);
            echo 'Ce lien est invalide ou a expiré.';
            return;
        }

        if (strtotime((string) $share['expires_at']) < time()) {
            http_response_code(410);
            echo 'Ce lien a expiré.';
            return;
        }

        if (!is_file((string) $share['storage_path'])) {
            http_response_code(404);
            echo 'Fichier introuvable.';
            return;
        }

        $fileName = str_replace(['"', "\r", "\n"], '', basename((string) $share['original_name']));

        header('Content-Type: ' . $share['mime_type']);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize((string) $share['storage_path']));
        readfile((string) $share['storage_path']);
        exit;
    }
}
