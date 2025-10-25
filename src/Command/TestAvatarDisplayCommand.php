<?php

namespace App\Command;

use App\Service\PdfGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-avatar-display',
    description: 'Test l\'affichage de l\'avatar dans la carte membre'
)]
class TestAvatarDisplayCommand extends Command
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

        $io->title('🖼️ Test Affichage Avatar');

        // Test avec des données incluant un avatar data URI
        $testData = [
            'avatar' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
            'name' => 'Test User',
            'phone' => '+229 01 23 45 67',
            'nationality' => 'Bénin',
            'roleBadge' => 'MEMBRE',
            'roleTitle' => 'MEMBER\nBINAJIA',
            'memberId' => 'BjNg-2025-001',
            'expiry' => '01/06/2028',
            'joinDate' => '01/06/2024',
            'qrCode' => null,
        ];

        try {
            $io->writeln("🖼️ Test avec avatar data URI...");

            // Générer le PDF de test
            $filename = 'test_avatar_display.pdf';
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
                $io->success('✅ Carte avec avatar générée avec succès');
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
