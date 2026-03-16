<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewPath = BASE_PATH . '/templates/' . $template . '.php';
        require BASE_PATH . '/templates/layout.php';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    public static function phpUploadMaxBytes(): int
    {
        $uploadMax = self::iniSizeToBytes((string) ini_get('upload_max_filesize'));
        $postMax = self::iniSizeToBytes((string) ini_get('post_max_size'));

        return min($uploadMax, $postMax);
    }

    public static function fileIconMeta(?string $extension = null): array
    {
        $ext = strtolower(trim((string) $extension));

        return match ($ext) {
            'pdf' => [
                'icon' => 'bi bi-file-earmark-pdf',
                'class' => 'file-icon-pdf',
                'label' => 'PDF',
            ],
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => [
                'icon' => 'bi bi-file-earmark-image',
                'class' => 'file-icon-image',
                'label' => 'Image',
            ],
            'doc', 'docx', 'odt' => [
                'icon' => 'bi bi-file-earmark-word',
                'class' => 'file-icon-doc',
                'label' => 'Document',
            ],
            'xls', 'xlsx', 'ods', 'csv' => [
                'icon' => 'bi bi-file-earmark-spreadsheet',
                'class' => 'file-icon-sheet',
                'label' => 'Tableur',
            ],
            'txt', 'log', 'md' => [
                'icon' => 'bi bi-file-earmark-text',
                'class' => 'file-icon-text',
                'label' => 'Texte',
            ],
            'json', 'xml', 'html', 'css', 'js', 'php' => [
                'icon' => 'bi bi-file-earmark-code',
                'class' => 'file-icon-code',
                'label' => 'Code',
            ],
            'zip', 'rar', '7z', 'tar', 'gz' => [
                'icon' => 'bi bi-file-earmark-zip',
                'class' => 'file-icon-archive',
                'label' => 'Archive',
            ],
            'mp3', 'wav', 'ogg', 'flac' => [
                'icon' => 'bi bi-file-earmark-music',
                'class' => 'file-icon-audio',
                'label' => 'Audio',
            ],
            'mp4', 'mov', 'avi', 'mkv', 'webm' => [
                'icon' => 'bi bi-file-earmark-play',
                'class' => 'file-icon-video',
                'label' => 'Vidéo',
            ],
            default => [
                'icon' => 'bi bi-file-earmark',
                'class' => 'file-icon-default',
                'label' => 'Fichier',
            ],
        };
    }

    
    public static function asset(string $path): string
    {
        return 'public/assets/' . ltrim($path, '/');
    }
}