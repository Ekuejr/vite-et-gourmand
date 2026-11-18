<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Téléversement sécurisé des images de menu.
 *  - type réel vérifié avec finfo (on ne fait pas confiance à l'extension ni au type envoyé par le navigateur)
 *  - formats autorisés : JPEG, PNG, WebP ; taille max 2 Mo
 *  - nom de fichier aléatoire (impossible d'écraser un fichier ou d'injecter un chemin "../")
 *  - le dossier uploads n'exécute pas de PHP (voir public/uploads/.htaccess)
 */
final class ImageUploader
{
    private const MAX_SIZE = 2 * 1024 * 1024;
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return string chemin public de l'image (ex. /uploads/abc123.jpg) */
    public static function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new \DomainException('Le fichier n\'a pas pu être téléversé.');
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw new \DomainException('Image trop lourde (2 Mo maximum).');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \DomainException('Format non autorisé (JPEG, PNG ou WebP uniquement).');
        }
        $name = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];
        $dest = dirname(__DIR__, 2) . '/public/uploads/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Enregistrement de l\'image impossible.');
        }
        return '/uploads/' . $name;
    }

    public static function delete(?string $publicPath): void
    {
        if ($publicPath && str_starts_with($publicPath, '/uploads/')) {
            $file = dirname(__DIR__, 2) . '/public' . $publicPath;
            if (is_file($file) && basename($file) === basename($publicPath)) {
                unlink($file);
            }
        }
    }
}
