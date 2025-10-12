<?php

namespace App\Service;

class FileUploader
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    /**
     * Save a user's avatar from a base64 data URL.
     * - Crops to 3:4 ratio (portrait)
     * - Resizes to 300x400
     * - Stores at public/media/avatars/{userId}.jpg
     *
     * @return string Public path starting with /media/avatars/...
     * @throws \RuntimeException on failure
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
                // Too wide: crop width
                $newW = (int) round($srcH * $targetRatio);
                $newH = $srcH;
                $srcX = (int) max(0, floor(($srcW - $newW) / 2));
                $srcY = 0;
            } else {
                // Too tall: crop height
                $newW = $srcW;
                $newH = (int) round($srcW / $targetRatio);
                $srcX = 0;
                $srcY = (int) max(0, floor(($srcH - $newH) / 2));
            }

            $crop = imagecreatetruecolor($newW, $newH);
            imagecopy($crop, $src, 0, 0, $srcX, $srcY, $newW, $newH);

            // Resize to 300x400
            $outW = 300;
            $outH = 400;
            $dst = imagecreatetruecolor($outW, $outH);
            imagecopyresampled($dst, $crop, 0, 0, 0, 0, $outW, $outH, $newW, $newH);

            imagedestroy($crop);

            $dir = $this->projectDir . '/public/media/avatars';
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    throw new \RuntimeException('Cannot create avatars directory');
                }
            }

            // Basic writability check
            if (!is_writable($dir)) {
                throw new \RuntimeException('Avatar directory is not writable: ' . $dir);
            }

            // Detect available output format (prefer JPEG)
            $canJpeg = function_exists('imagejpeg');
            $canPng  = function_exists('imagepng');

            if ($canJpeg) {
                $filename = sprintf('%d.jpg', $userId);
                $fsPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
                if (!@imagejpeg($dst, $fsPath, 90)) {
                    $last = error_get_last();
                    $jpegErr = $last['message'] ?? 'unknown error';
                    // If JPEG write fails, try PNG fallback if available
                    if ($canPng) {
                        $filename = sprintf('%d.png', $userId);
                        $fsPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
                        if (!@imagepng($dst, $fsPath, 6)) {
                            $last = error_get_last();
                            $pngErr = $last['message'] ?? 'unknown error';
                            throw new \RuntimeException('Failed to save avatar (JPEG failed: ' . $jpegErr . ', PNG failed: ' . $pngErr . ') at ' . $fsPath);
                        }
                        $publicPath = '/media/avatars/' . $filename;
                    } else {
                        throw new \RuntimeException('Failed to save avatar (JPEG failed: ' . $jpegErr . ') at ' . $fsPath);
                    }
                } else {
                    $publicPath = '/media/avatars/' . $filename;
                }
            } elseif ($canPng) {
                $filename = sprintf('%d.png', $userId);
                $fsPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
                // 0 (no compression) to 9
                if (!@imagepng($dst, $fsPath, 6)) {
                    $last = error_get_last();
                    $pngErr = $last['message'] ?? 'unknown error';
                    throw new \RuntimeException('Failed to save avatar (PNG failed: ' . $pngErr . ') at ' . $fsPath);
                }
                $publicPath = '/media/avatars/' . $filename;
            } else {
                throw new \RuntimeException('GD output (JPEG/PNG) not available');
            }
            imagedestroy($dst);
            imagedestroy($src);

            return $publicPath;
        } finally {
            if (is_resource($src)) {
                // In case of exceptions before manual destroy
                @imagedestroy($src);
            }
        }
    }
}