<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    private Environment $twig;
    private string $publicDir;

    public function __construct(Environment $twig, string $publicDir)
    {
        $this->twig = $twig;
        $this->publicDir = $publicDir;
    }

    public function generatePdf(string $template, array $params, string $outputPath, string $paper = 'A4', string $orientation = 'portrait'): void
    {
        $options = new Options();
        
        // Configuration de base
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);
        $options->setChroot($this->publicDir);
        
        // Options critiques pour les styles CSS
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultMediaType', 'screen');
        $options->set('isCssFloatEnabled', true);
        $options->set('isJavascriptEnabled', false); // Désactiver JS pour éviter les conflits
        
        // Options de debug désactivées pour la production
        $options->set('debugKeepTemp', false);
        $options->set('debugCss', false);
        $options->set('debugLayout', false);

        $dompdf = new Dompdf($options);
        $html = $this->twig->render($template, $params);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        // Ensure target directory exists
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Cannot create PDF directory: ' . $dir);
            }
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException('PDF directory not writable: ' . $dir);
        }

        $bytes = file_put_contents($outputPath, $pdfOutput);
        if ($bytes === false || $bytes === 0) {
            $last = error_get_last();
            $err = $last['message'] ?? 'unknown error';
            throw new \RuntimeException('Failed to save PDF at ' . $outputPath . ' (' . $err . ')');
        }
    }
}
