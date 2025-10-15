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
    public function generate(string $data, int $size = 250, ?string $logoPath = null, ?string $label = null): string
    {
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

        if ($logoPath && file_exists($logoPath)) {
            $builder = $builder->logoPath($logoPath)
                              ->logoResizeToWidth(50)
                              ->logoPunchoutBackground(true);
        }

        if ($label) {
            $builder = $builder->labelText($label)
                              ->labelFont(new OpenSans(16))
                              ->labelAlignment(LabelAlignment::Center);
        }

        $result = $builder->build();

        return $result->getDataUri();
    }

    public function saveToFile(string $data, string $filePath, int $size = 250): void
    {
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

        $result = $builder->build();
        $result->saveToFile($filePath);
    }
}
