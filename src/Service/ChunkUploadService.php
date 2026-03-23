<?php
declare(strict_types=1);

namespace App\Service;

use App\Config\Config;
use InvalidArgumentException;
use RuntimeException;

final class ChunkUploadService
{
    private const CHUNK_TTL = 3600; // 1 heure

    private string $tmpPath;

    public function __construct()
    {
        $this->tmpPath = Config::path('storage/tmp/chunks');

        if (!is_dir($this->tmpPath)) {
            mkdir($this->tmpPath, 0775, true);
        }
    }

    /**
     * Crée une session d'upload, retourne un uploadId unique (32 hex chars).
     */
    public function initSession(string $originalName, int $totalSize, int $totalChunks): string
    {
        $uploadId = bin2hex(random_bytes(16));
        $dir      = $this->sessionDir($uploadId);

        mkdir($dir, 0775, true);

        file_put_contents($dir . '/.meta.json', json_encode([
            'original_name' => $originalName,
            'total_size'    => $totalSize,
            'total_chunks'  => $totalChunks,
            'created_at'    => time(),
        ]));

        return $uploadId;
    }

    /**
     * Sauvegarde un chunk sur disque. Retourne le nombre de chunks reçus.
     */
    public function storeChunk(string $uploadId, int $chunkIndex, string $tmpChunkPath): int
    {
        $dir = $this->sessionDir($uploadId);

        if (!is_dir($dir)) {
            throw new RuntimeException("Session d'upload introuvable.");
        }

        if (!is_uploaded_file($tmpChunkPath)) {
            throw new RuntimeException('Chunk invalide.');
        }

        $chunkFile = $dir . '/' . str_pad((string) $chunkIndex, 4, '0', STR_PAD_LEFT);

        if (!file_exists($chunkFile)) {
            if (!move_uploaded_file($tmpChunkPath, $chunkFile)) {
                throw new RuntimeException('Impossible de sauvegarder le chunk.');
            }
        }

        return count(glob($dir . '/[0-9][0-9][0-9][0-9]') ?: []);
    }

    /**
     * Assemble tous les chunks dans l'ordre. Retourne le chemin du fichier assemblé.
     */
    public function assemble(string $uploadId): string
    {
        $dir  = $this->sessionDir($uploadId);
        $meta = $this->getMeta($uploadId);

        $totalChunks   = (int) $meta['total_chunks'];
        $assembledPath = $dir . '/assembled';

        $out = fopen($assembledPath, 'wb');

        if ($out === false) {
            throw new RuntimeException('Impossible de créer le fichier assemblé.');
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $dir . '/' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            if (!file_exists($chunkFile)) {
                fclose($out);
                throw new RuntimeException("Chunk $i manquant.");
            }

            $in = fopen($chunkFile, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);

        return $assembledPath;
    }

    /**
     * Lit les métadonnées d'une session.
     */
    public function getMeta(string $uploadId): array
    {
        $metaFile = $this->sessionDir($uploadId) . '/.meta.json';

        if (!file_exists($metaFile)) {
            throw new RuntimeException("Métadonnées d'upload introuvables.");
        }

        $data = json_decode((string) file_get_contents($metaFile), true);

        if (!is_array($data)) {
            throw new RuntimeException('Métadonnées corrompues.');
        }

        return $data;
    }

    /**
     * Supprime le répertoire temporaire d'une session.
     */
    public function cleanup(string $uploadId): void
    {
        $dir = $this->sessionDir($uploadId);

        if (!is_dir($dir)) {
            return;
        }

        foreach (new \DirectoryIterator($dir) as $item) {
            if ($item->isFile()) {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    /**
     * Supprime les sessions expirées (> CHUNK_TTL secondes).
     * Appelé à chaque réception de chunk.
     */
    public function pruneExpired(): void
    {
        if (!is_dir($this->tmpPath)) {
            return;
        }

        foreach (new \DirectoryIterator($this->tmpPath) as $item) {
            if (!$item->isDir() || $item->isDot()) {
                continue;
            }

            $metaFile  = $item->getPathname() . '/.meta.json';
            $createdAt = 0;

            if (file_exists($metaFile)) {
                $data      = json_decode((string) file_get_contents($metaFile), true);
                $createdAt = isset($data['created_at']) ? (int) $data['created_at'] : 0;
            } else {
                $createdAt = $item->getMTime();
            }

            if (time() - $createdAt > self::CHUNK_TTL) {
                foreach (new \DirectoryIterator($item->getPathname()) as $file) {
                    if ($file->isFile()) {
                        unlink($file->getPathname());
                    }
                }
                rmdir($item->getPathname());
            }
        }
    }

    private function sessionDir(string $uploadId): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $uploadId)) {
            throw new InvalidArgumentException('Upload ID invalide.');
        }

        return $this->tmpPath . '/' . $uploadId;
    }
}
