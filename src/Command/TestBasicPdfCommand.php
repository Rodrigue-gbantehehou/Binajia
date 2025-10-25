<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-basic-pdf',
    description: 'Test basique de génération PDF'
)]
class TestBasicPdfCommand extends Command
{
    public function __construct(private string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test basique génération PDF');

        try {
            $io->writeln('📁 Répertoire projet: ' . $this->projectDir);

            // Vérifier si les répertoires existent
            $templateDir = $this->projectDir . '/templates';
            $uploadDir = $this->projectDir . '/var/uploads';

            $io->writeln('📂 Templates: ' . (is_dir($templateDir) ? 'OK' : 'MANQUANT'));
            $io->writeln('📂 Uploads: ' . (is_dir($uploadDir) ? 'OK' : 'MANQUANT'));

            // Vérifier si les templates existent
            $cardTemplate = $templateDir . '/membership/card.html.twig';
            $receiptTemplate = $templateDir . '/invoice/receipt.html.twig';

            $io->writeln('🎴 Template carte: ' . (file_exists($cardTemplate) ? 'OK' : 'MANQUANT'));
            $io->writeln('📄 Template reçu: ' . (file_exists($receiptTemplate) ? 'OK' : 'MANQUANT'));

            // Tester la création des services de base
            $io->section('🏗️ Test création services');

            try {
                $pdfGenerator = new \App\Service\PdfGeneratorService(
                    new \Twig\Environment(new \Twig\Loader\FilesystemLoader($templateDir)),
                    $uploadDir
                );
                $io->writeln('✅ PdfGeneratorService créé');
            } catch (\Exception $e) {
                $io->writeln('❌ Erreur PdfGeneratorService: ' . $e->getMessage());
            }

            try {
                $qrCodeService = new \App\Service\QrCodeService($uploadDir . '/private');
                $io->writeln('✅ QrCodeService créé');
            } catch (\Exception $e) {
                $io->writeln('❌ Erreur QrCodeService: ' . $e->getMessage());
            }

            // Test MembershipCardService sera fait dans les vraies commandes avec injection de dépendance

        } catch (\Exception $e) {
            $io->error('❌ Erreur générale: ' . $e->getMessage());
            $io->writeln('Stack: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
