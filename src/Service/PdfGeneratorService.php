<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    private Environment $twig;
    private string $uploadDir;

    public function __construct(Environment $twig, string $uploadDir)
    {
        $this->twig = $twig;
        $this->uploadDir = $uploadDir;
    }

    /**
     * Génère un PDF à partir d’un template Twig.
     * 
     * @param string $template Nom du template (ex: 'pdf/carte.html.twig')
     * @param array  $params   Variables Twig
     * @param string $filename Nom du fichier PDF (ex: 'carte_123.pdf')
     * @param string $paper    Taille du papier ('A4' par défaut)
     * @param string $orientation Orientation du PDF ('portrait' ou 'landscape')
     * 
     * @return string Chemin complet du PDF généré
     * @throws \RuntimeException
     */
    public function generatePdf(string $template, array $params, string $filename, string $paper = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options();

        // 🔧 Configuration de base et sécurité
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false); // par sécurité
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultMediaType', 'screen');
        $options->set('isCssFloatEnabled', true);
        $options->set('isJavascriptEnabled', false);

        // 🔐 Chroot limité aux assets nécessaires (si tu veux charger des images depuis public/)
        $options->setChroot([
            $this->uploadDir,
            __DIR__ . '/../../public', // utile si tes templates Twig chargent des logos ou styles depuis public/
        ]);

        $dompdf = new Dompdf($options);
        $html = $this->twig->render($template, $params);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        // 📁 Emplacement sécurisé du fichier
        $dir = $this->uploadDir . '/pdf';
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Cannot create PDF directory: ' . $dir);
            }
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException('PDF directory not writable: ' . $dir);
        }

        $outputPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $bytes = file_put_contents($outputPath, $pdfOutput);
        if ($bytes === false || $bytes === 0) {
            $last = error_get_last();
            $err = $last['message'] ?? 'unknown error';
            throw new \RuntimeException('Failed to save PDF at ' . $outputPath . ' (' . $err . ')');
        }

        return $outputPath;
    }
}
