<?php
declare(strict_types=1);

namespace App\Service;

use App\Config\Config;
use RuntimeException;

final class FileStorageService
{
    private string $storagePath;

    private array $allowedExtensions = [
        'pdf', 'txt', 'md', 'zip', 'rar', '7z',
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'csv', 'mp3', 'mp4', 'psd'
    ];

    public function __construct()
    {
        $relative = (string) Config::get('STORAGE_PATH', 'storage/files');
        $this->storagePath = Config::path($relative);

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }
    }

    public function store(array $uploadedFile): array
    {
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur durant l’upload.');
        }

        $tmpName = $uploadedFile['tmp_name'] ?? '';
        $originalName = $uploadedFile['name'] ?? 'file';
        $size = (int) ($uploadedFile['size'] ?? 0);

        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException('Fichier temporaire invalide.');
        }

        if ($size <= 0) {
            throw new RuntimeException('Le fichier est vide.');
        }

        $maxSize = (int) Config::get('MAX_UPLOAD_SIZE', 104857600);

        if ($size > $maxSize) {
            throw new RuntimeException('Le fichier dépasse la taille maximale autorisée.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw new RuntimeException('Extension non autorisée.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName) ?: 'application/octet-stream';
        finfo_close($finfo);

        $sha256 = hash_file('sha256', $tmpName);
        $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $this->storagePath . '/' . $storedName;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new RuntimeException('Impossible de déplacer le fichier.');
        }

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size_bytes' => $size,
            'sha256' => $sha256,
            'storage_path' => $destination,
        ];
    }
}