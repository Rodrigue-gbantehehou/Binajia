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
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);
        $options->setChroot($this->publicDir);

        $dompdf = new Dompdf($options);
        $html = $this->twig->render($template, $params);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $pdfOutput = $dompdf->output();
        @file_put_contents($outputPath, $pdfOutput);
    }
}
