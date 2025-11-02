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

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    /**
     * Génère un PDF à partir d’un template Twig.
     * 
     * @param string $template Nom du template (ex: 'pdf/carte.html.twig')
     * @param array  $params   Variables Twig
     * @param string $filename Nom du fichier PDF (ex: 'carte_123.pdf')
     * @param string $paper    Taille du papier ('A4' par défaut)
     * @param string $orientation Orientation du PDF ('portrait' ou 'landscape')
     * @param string $outputDir Dossier de destination (optionnel, utilise uploadDir/pdf par défaut)
     * 
     * @return string Chemin complet du PDF généré
     * @throws \RuntimeException
     */
    public function generatePdf(string $template, array $params, string $filename, string $paper = 'A4', string $orientation = 'portrait', string $outputDir = null): string
    {
        $options = new Options();

        // 🔧 Configuration de base et sécurité
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false); // par sécurité
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultMediaType', 'print'); // Changé de 'screen' à 'print' pour un meilleur rendu PDF
        $options->set('isCssFloatEnabled', true);
        $options->set('isJavascriptEnabled', false);
        $options->set('isRemoteEnabled', true);
        
        // Chemin absolu vers le répertoire public
        $publicDir = realpath(__DIR__ . '/../../public');
        
        // 🔐 Configuration des chemins autorisés
        $options->setChroot([
            $this->uploadDir,
            $publicDir,
            '/',  // Nécessaire pour certaines configurations serveur
        ]);
        
        // Définir le répertoire de base pour les URLs
        $options->set('basePath', $publicDir);
        
        // Convertir les chemins relatifs en absolus dans les paramètres
        foreach ($params as $key => $value) {
            if (is_string($value) && strpos($value, 'media/') === 0) {
                $params[$key] = $publicDir . '/' . $value;
            }
        }

        $dompdf = new Dompdf($options);
        
        // Rendre le template avec les paramètres
        $html = $this->twig->render($template, $params);
        
        // Remplacer les chemins des assets
        $html = str_replace(
            ['src="media/', 'src="/media/'],
            ['src="' . $publicDir . '/media/', 'src="' . $publicDir . '/media/'],
            $html
        );
        
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        
        // Augmenter la mémoire et le temps d'exécution pour les gros documents
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);
        
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        // 📁 Emplacement du fichier - utiliser le dossier spécifié ou par défaut
        $dir = $outputDir ?: ($this->uploadDir . '/pdf');

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
