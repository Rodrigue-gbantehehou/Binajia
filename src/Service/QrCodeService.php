<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    private string $storageDir;

    public function __construct(string $privateStorageDir)
    {
        $this->storageDir = rtrim($privateStorageDir, '/');
    }

    /**
     * Génère un QR code et retourne sa Data URI (utile pour affichage direct dans Twig)
     */
    public function generate(string $data, int $size = 250, ?string $logoPath = null, ?string $label = null): string
    {
        $hasLogo = $logoPath && file_exists($logoPath);
        $hasLabel = !empty($label);

        if ($hasLogo && $hasLabel) {
            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: $size,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(245, 158, 11), // couleur #f59e0b
                backgroundColor: new Color(255, 255, 255),
                logoPath: $logoPath,
                logoResizeToWidth: 50,
                logoPunchoutBackground: true,
                labelText: $label,
                labelFont: new OpenSans(16),
                labelAlignment: LabelAlignment::Center
            );
        } elseif ($hasLogo) {
            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: $size,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(245, 158, 11), // couleur #f59e0b
                backgroundColor: new Color(255, 255, 255),
                logoPath: $logoPath,
                logoResizeToWidth: 50,
                logoPunchoutBackground: true
            );
        } elseif ($hasLabel) {
            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: $size,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(245, 158, 11), // couleur #f59e0b
                backgroundColor: new Color(255, 255, 255),
                labelText: $label,
                labelFont: new OpenSans(16),
                labelAlignment: LabelAlignment::Center
            );
        } else {
            $builder = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: $size,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(245, 158, 11), // couleur #f59e0b
                backgroundColor: new Color(255, 255, 255)
            );
        }

        return $builder->build()->getDataUri();
    }

    /**
     * Génère et enregistre un QR code dans un répertoire privé sécurisé
     */
    public function saveToFile(string $data, string $relativePath, int $size = 250): string
    {
        $fullPath = $this->storageDir . '/' . ltrim($relativePath, '/');

        // Crée le dossier si nécessaire
        $dir = \dirname($fullPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0770, true) && !is_dir($dir)) {
                throw new \RuntimeException("Impossible de créer le dossier : $dir");
            }
        }

        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(245, 158, 11),
            backgroundColor: new Color(255, 255, 255)
        );

        $result = $builder->build();
        $result->saveToFile($fullPath);

        return $fullPath;
    }
}
