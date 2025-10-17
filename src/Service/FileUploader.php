<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct(
        private readonly string $uploadDir, // injecté depuis services.yaml
    ) {
    }

    /**
     * Enregistre un avatar utilisateur depuis une image base64.
     * - Crop ratio 3:4
     * - Redimensionne à 300x400
     * - Stocke dans var/uploads/membres/{userId}.jpg (hors du dossier public)
     *
     * @return string Chemin absolu du fichier sauvegardé
     * @throws \RuntimeException en cas d’erreur
     */
    public function saveAvatarFromDataUrl(string $dataUrl, int $userId): string
    {
        if ($dataUrl === '' || !str_starts_with($dataUrl, 'data:image')) {
            throw new \RuntimeException('Invalid image data URL');
        }

        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) {
            throw new \RuntimeException('Invalid image payload');
        }

        $bin = base64_decode($parts[1]);
        if ($bin === false) {
            throw new \RuntimeException('Unable to decode image');
        }

        $src = @imagecreatefromstring($bin);
        if ($src === false) {
            throw new \RuntimeException('Unsupported image format');
        }

        try {
            $srcW = imagesx($src);
            $srcH = imagesy($src);
            $targetRatio = 3 / 4;
            $srcRatio = $srcW / max(1, $srcH);

            if ($srcRatio > $targetRatio) {
                // Trop large → on crop la largeur
                $newW = (int) round($srcH * $targetRatio);
                $newH = $srcH;
                $srcX = (int) max(0, floor(($srcW - $newW) / 2));
                $srcY = 0;
            } else {
                // Trop haut → on crop la hauteur
                $newW = $srcW;
                $newH = (int) round($srcW / $targetRatio);
                $srcX = 0;
                $srcY = (int) max(0, floor(($srcH - $newH) / 2));
            }

            $crop = imagecreatetruecolor($newW, $newH);
            imagecopy($crop, $src, 0, 0, $srcX, $srcY, $newW, $newH);

            // Redimensionne à 300x400
            $outW = 300;
            $outH = 400;
            $dst = imagecreatetruecolor($outW, $outH);
            imagecopyresampled($dst, $crop, 0, 0, 0, 0, $outW, $outH, $newW, $newH);

            imagedestroy($crop);

            // 📁 Dossier sécurisé (hors public)
            $dir = $this->uploadDir . '/membres';
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    throw new \RuntimeException('Cannot create membres directory: ' . $dir);
                }
            }

            if (!is_writable($dir)) {
                throw new \RuntimeException('Upload directory not writable: ' . $dir);
            }

            // Détection format (JPEG > PNG)
            $canJpeg = function_exists('imagejpeg');
            $canPng  = function_exists('imagepng');

            if ($canJpeg) {
                $filename = sprintf('%d.jpg', $userId);
                $fsPath = $dir . DIRECTORY_SEPARATOR . $filename;
                if (!@imagejpeg($dst, $fsPath, 90)) {
                    $last = error_get_last();
                    $jpegErr = $last['message'] ?? 'unknown';
                    if ($canPng) {
                        $filename = sprintf('%d.png', $userId);
                        $fsPath = $dir . DIRECTORY_SEPARATOR . $filename;
                        if (!@imagepng($dst, $fsPath, 6)) {
                            $last = error_get_last();
                            $pngErr = $last['message'] ?? 'unknown';
                            throw new \RuntimeException("Failed to save image (JPEG: $jpegErr, PNG: $pngErr)");
                        }
                    } else {
                        throw new \RuntimeException("Failed to save avatar (JPEG error: $jpegErr)");
                    }
                }
            } elseif ($canPng) {
                $filename = sprintf('%d.png', $userId);
                $fsPath = $dir . DIRECTORY_SEPARATOR . $filename;
                if (!@imagepng($dst, $fsPath, 6)) {
                    $last = error_get_last();
                    $pngErr = $last['message'] ?? 'unknown';
                    throw new \RuntimeException("Failed to save avatar (PNG error: $pngErr)");
                }
            } else {
                throw new \RuntimeException('GD output (JPEG/PNG) not available');
            }

            imagedestroy($dst);
            imagedestroy($src);

            // 🔒 Retourne le chemin complet (non public)
            return $fsPath;

        } finally {
            if (is_resource($src) || $src instanceof \GdImage) {
                @imagedestroy($src);
            }
        }
    }

    /**
     * Enregistre un fichier uploadé dans un sous-dossier spécifique.
     *
     * @param UploadedFile $file Le fichier uploadé
     * @param string $subDir Le sous-dossier de destination
     * @param string|null $filename Nom de fichier personnalisé (si null, génère un nom unique)
     * @return string Chemin absolu du fichier sauvegardé
     * @throws \RuntimeException en cas d’erreur
     */
    public function saveUploadedFile(UploadedFile $file, string $subDir, string $filename = null): string
    {
        // Crée le dossier s'il n'existe pas dans le répertoire media/
        $dir = $this->uploadDir . '/media/' . $subDir;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Cannot create directory: ' . $dir);
            }
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException('Upload directory not writable: ' . $dir);
        }

        // Génère un nom de fichier unique si pas fourni
        if ($filename === null) {
            $extension = $file->guessExtension() ?? 'bin';
            $filename = uniqid() . '.' . $extension;
        }

        // Déplace le fichier
        $file->move($dir, $filename);

        return $dir . '/' . $filename;
    }
}
