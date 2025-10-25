<?php

namespace App\Command;

use App\Service\PdfGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-card-header',
    description: 'Test la carte avec le nouveau texte "Carte de Membre / Membership card" en haut de la photo'
)]
class TestCardHeaderCommand extends Command
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

        $io->title('📋 Test Carte avec Header Photo');

        // Test avec des données complètes incluant le texte header
        $testData = [
            'avatar' => null, // Test sans avatar
            'name' => 'Marie Claire Dupont',
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
            $io->writeln("📝 Test de la carte avec header photo...");

            // Générer le PDF de test
            $filename = 'test_card_header.pdf';
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
                $io->success('✅ Carte avec header photo générée avec succès');
                $io->writeln("📁 Fichier: $outputPath");
                $io->writeln("📏 Taille: " . filesize($outputPath) . " octets");

                // Vérifier que le fichier n'est pas vide
                if (filesize($outputPath) > 1000) {
                    $io->success('✅ PDF généré avec du contenu');
                } else {
                    $io->warning('⚠️ PDF généré mais très petit - vérifier le contenu');
                }

                // Vérifier que le texte header est présent dans le HTML généré
                $html = $this->pdfGenerator->getTwig()->render('membership/card.html.twig', $testData);
                if (str_contains($html, 'Carte de Membre')) {
                    $io->success('✅ Texte "Carte de Membre / Membership card" présent dans le template');
                } else {
                    $io->warning('⚠️ Texte header non trouvé dans le template');
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
