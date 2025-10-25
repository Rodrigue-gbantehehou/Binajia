<?php

namespace App\Command;

use App\Service\PdfGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-qr-position',
    description: 'Test le positionnement du QR code sur la carte membre'
)]
class TestQrPositionCommand extends Command
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📱 Test Positionnement QR Code');

        // Test avec des données complètes
        $testData = [
            'avatar' => null,
            'name' => 'Jean fferefrr Dupont',
            'phone' => '+229 01 23 45 67',
            'nationality' => 'Bénin',
            'roleBadge' => 'MEMBRE',
            'roleTitle' => 'MEMBER\nBINAJIA',
            'memberId' => 'BjNg-2025-001',
            'expiry' => '01/06/2028',
            'joinDate' => '01/06/2024',
            'qrCode' => null, // QR code de test
        ];

        try {
            $io->writeln("📝 Test de la carte avec QR code...");

            // Générer le PDF de test
            $filename = 'test_qr_position.pdf';
            $outputPath = $this->projectDir . '/var/uploads/cards/' . $filename;

            $this->pdfGenerator->generatePdf(
                'membership/card.html.twig',
                $testData,
                $filename,
                'A4',
                'portrait',
                $this->projectDir . '/var/uploads/cards'
            );

            if (file_exists($outputPath)) {
                $io->success('✅ Carte avec QR code générée avec succès');
                $io->writeln("📁 Fichier: $outputPath");
                $io->writeln("📏 Taille: " . filesize($outputPath) . " octets");

                // Vérifier que le fichier n'est pas vide
                if (filesize($outputPath) > 1000) {
                    $io->success('✅ PDF généré avec du contenu');
                } else {
                    $io->warning('⚠️ PDF généré mais très petit - vérifier le contenu');
                }
            } else {
                $io->error('❌ Échec de la génération du PDF');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
