<?php

namespace App\Command;

use App\Service\PdfGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-pdf-styles',
    description: 'Test spécifique pour vérifier que les styles CSS sont appliqués dans le PDF'
)]
class TestPdfStylesCommand extends Command
{
    public function __construct(
        private PdfGeneratorService $pdfService,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🎨 Test des styles CSS dans la génération PDF - BINAJIA');

        try {
            // Créer un avatar de test avec chemin absolu
            $io->section('📷 Création d\'un avatar de test');
            $avatarPath = $this->createTestAvatar($io);

            // Test 1: Génération avec avatar
            $io->section('🎴 Test génération PDF avec avatar et styles');
            $this->testPdfWithStyles($avatarPath, $io);

            // Test 2: Génération sans avatar
            $io->section('🎴 Test génération PDF sans avatar');
            $this->testPdfWithoutAvatar($io);

            // Test 3: Vérifier la taille du PDF généré
            $io->section('📊 Vérification de la qualité du PDF');
            $this->verifyPdfQuality($io);

            $io->success('🎉 Tests des styles PDF terminés avec succès !');

        } catch (\Exception $e) {
            $io->error('❌ Erreur pendant le test : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createTestAvatar(SymfonyStyle $io): string
    {
        $avatarDir = $this->projectDir . '/public/media/avatars';
        $avatarPath = $avatarDir . '/test_styles_avatar.svg';

        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0775, true);
        }

        // Créer un avatar SVG coloré pour tester les styles
        $svgContent = <<<SVG
<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#0a4b1e;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#2D7A4F;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="200" height="200" fill="url(#grad1)"/>
    <circle cx="100" cy="70" r="35" fill="#F59E0B"/>
    <ellipse cx="100" cy="150" rx="45" ry="30" fill="#F59E0B"/>
    <text x="100" y="185" text-anchor="middle" font-family="Arial" font-size="14" fill="white" font-weight="bold">TEST</text>
</svg>
SVG;

        file_put_contents($avatarPath, $svgContent);
        $io->writeln("✅ Avatar de test créé avec styles : " . basename($avatarPath));

        return $avatarPath; // Retourner le chemin absolu
    }

    private function testPdfWithStyles(string $avatarPath, SymfonyStyle $io): void
    {
        $testPath = $this->projectDir . '/public/media/test_styles_with_avatar.pdf';
        
        $this->pdfService->generatePdf(
            'membership/card_pdf_modern.html.twig',
            [
                'avatar' => $avatarPath,
                'name' => 'Jean-Baptiste KOUASSI',
                'phone' => '+229 97 12 34 56',
                'nationality' => 'Bénin',
                'roleBadge' => 'AMBASSADEUR',
                'roleTitle' => 'AMBASSADEUR\nBINAJIA',
                'memberId' => 'BNJ001234',
                'expiry' => '31/12/2025',
                'joinDate' => date('d/m/Y'),
            ],
            $testPath,
            'A6',
            'landscape'
        );

        if (file_exists($testPath)) {
            $size = filesize($testPath);
            $io->success("✅ PDF avec avatar généré : $size bytes");
            $io->writeln("📁 Fichier : " . basename($testPath));
            
            // Garder le fichier pour inspection manuelle
            $io->note("Le fichier PDF a été conservé pour vérification manuelle des styles");
        } else {
            $io->error("❌ Le fichier PDF n'a pas été créé");
        }
    }

    private function testPdfWithoutAvatar(SymfonyStyle $io): void
    {
        $testPath = $this->projectDir . '/public/media/test_styles_without_avatar.pdf';
        
        $this->pdfService->generatePdf(
            'membership/card_pdf_modern.html.twig',
            [
                'avatar' => null,
                'name' => 'Marie ADEBAYO',
                'phone' => '+234 803 123 4567',
                'nationality' => 'Nigéria',
                'roleBadge' => 'MEMBRE',
                'roleTitle' => 'MEMBER\nBINAJIA',
                'memberId' => 'BNJ005678',
                'expiry' => '31/12/2025',
                'joinDate' => date('d/m/Y'),
            ],
            $testPath,
            'A6',
            'landscape'
        );

        if (file_exists($testPath)) {
            $size = filesize($testPath);
            $io->success("✅ PDF sans avatar généré : $size bytes");
            $io->writeln("📁 Fichier : " . basename($testPath));
            
            // Garder le fichier pour inspection manuelle
            $io->note("Le fichier PDF a été conservé pour vérification manuelle des styles");
        } else {
            $io->error("❌ Le fichier PDF n'a pas été créé");
        }
    }

    private function verifyPdfQuality(SymfonyStyle $io): void
    {
        $testFiles = [
            'test_styles_with_avatar.pdf',
            'test_styles_without_avatar.pdf'
        ];

        foreach ($testFiles as $filename) {
            $filepath = $this->projectDir . '/public/media/' . $filename;
            
            if (file_exists($filepath)) {
                $size = filesize($filepath);
                $io->writeln("📄 $filename : $size bytes");
                
                // Vérifier que le PDF n'est pas trop petit (indicateur de problème)
                if ($size < 10000) {
                    $io->warning("⚠️ Le PDF semble petit, vérifiez les styles");
                } elseif ($size > 100000) {
                    $io->success("✅ PDF de bonne taille, styles probablement appliqués");
                } else {
                    $io->writeln("ℹ️ PDF de taille normale");
                }
            }
        }

        $io->note("Vérifiez manuellement les fichiers PDF dans public/media/ pour confirmer que :");
        $io->listing([
            "Les couleurs sont appliquées (vert, orange)",
            "Les dégradés sont visibles",
            "Les polices sont correctes",
            "La mise en page est respectée",
            "Les drapeaux sont affichés",
            "L'avatar est intégré (si fourni)"
        ]);
    }
}
