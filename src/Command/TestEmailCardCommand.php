<?php

namespace App\Command;

use App\Service\EmailService;
use App\Service\MembershipCardService;
use App\Service\PdfGeneratorService;
use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email-card',
    description: 'Test les fonctionnalités d\'email et de génération de cartes'
)]
class TestEmailCardCommand extends Command
{
    public function __construct(
        private EmailService $emailService,
        private MembershipCardService $cardService,
        private PdfGeneratorService $pdfService,
        private MailerInterface $mailer,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🔍 Test des fonctionnalités Email et Cartes - BINAJIA');

        // Test 1: Configuration Email
        $io->section('📧 Test de la configuration email');
        $this->testEmailConfiguration($io);

        // Test 2: Connexion SMTP
        $io->section('🔌 Test de la connexion SMTP');
        $this->testSmtpConnection($io);

        // Test 3: Dossiers et permissions
        $io->section('📁 Test des dossiers et permissions');
        $this->testDirectoriesAndPermissions($io);

        // Test 4: Génération PDF
        $io->section('📄 Test de génération PDF');
        $this->testPdfGeneration($io);

        // Test 5: Envoi d'email de test
        $io->section('✉️ Test d\'envoi d\'email');
        $this->testEmailSending($io);

        $io->success('Tests terminés ! Vérifiez les résultats ci-dessus.');

        return Command::SUCCESS;
    }

    private function testEmailConfiguration(SymfonyStyle $io): void
    {
        $config = [
            'MAILER_DSN' => $_ENV['MAILER_DSN'] ?? 'Non défini',
            'MAIL_FROM_ADDRESS' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'Non défini',
            'MAIL_FROM_NAME' => $_ENV['MAIL_FROM_NAME'] ?? 'Non défini',
            'MAIL_HOST' => $_ENV['MAIL_HOST'] ?? 'Non défini',
            'MAIL_PORT' => $_ENV['MAIL_PORT'] ?? 'Non défini',
        ];

        foreach ($config as $key => $value) {
            $status = $value !== 'Non défini' ? '✅' : '❌';
            $io->writeln("$status $key: $value");
        }
    }

    private function testSmtpConnection(SymfonyStyle $io): void
    {
        try {
            $host = $_ENV['MAIL_HOST'] ?? '';
            $port = $_ENV['MAIL_PORT'] ?? 465;

            if (empty($host)) {
                $io->error('Host SMTP non configuré');
                return;
            }

            $connection = @fsockopen($host, (int)$port, $errno, $errstr, 10);
            
            if ($connection) {
                fclose($connection);
                $io->success("✅ Connexion SMTP réussie vers $host:$port");
            } else {
                $io->error("❌ Impossible de se connecter à $host:$port - $errstr ($errno)");
            }
        } catch (\Exception $e) {
            $io->error("❌ Erreur de connexion SMTP: " . $e->getMessage());
        }
    }

    private function testDirectoriesAndPermissions(SymfonyStyle $io): void
    {
        $directories = [
            'public/media' => $this->projectDir . '/public/media',
            'public/media/cards' => $this->projectDir . '/public/media/cards',
            'public/media/receipts' => $this->projectDir . '/public/media/receipts',
            'public/media/avatars' => $this->projectDir . '/public/media/avatars',
        ];

        foreach ($directories as $name => $path) {
            if (!is_dir($path)) {
                if (@mkdir($path, 0775, true)) {
                    $io->writeln("✅ Dossier $name créé");
                } else {
                    $io->writeln("❌ Impossible de créer le dossier $name");
                    continue;
                }
            } else {
                $io->writeln("✅ Dossier $name existe");
            }

            if (is_writable($path)) {
                $io->writeln("✅ Dossier $name est accessible en écriture");
            } else {
                $io->writeln("❌ Dossier $name n'est pas accessible en écriture");
            }
        }
    }

    private function testPdfGeneration(SymfonyStyle $io): void
    {
        try {
            $testPath = $this->projectDir . '/var/uploads/pdf/test_card.pdf';
            
            $this->pdfService->generatePdf(
                'membership/card_pdf_modern.html.twig',
                [
                    'avatar' => null,
                    'name' => 'Test User',
                    'phone' => '+229 12 34 56 78',
                    'nationality' => 'Bénin',
                    'roleBadge' => 'MEMBRE',
                    'roleTitle' => 'MEMBER\nBINAJIA',
                    'memberId' => 'TEST001',
                    'expiry' => '31/12/2025',
                    'joinDate' => date('d/m/Y'),
                ],
                'test_card.pdf',
                'A6',
                'landscape'
            );

            $fullPath = $this->projectDir . '/var/uploads/pdf/test_card.pdf';
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                $io->success("✅ PDF de test généré avec succès ($size bytes)");
                @unlink($fullPath); // Nettoyer le fichier de test
            } else {
                $io->error("❌ Le fichier PDF n'a pas été créé à l'emplacement attendu: $fullPath");
            }
        } catch (\Exception $e) {
            $io->error("❌ Erreur de génération PDF: " . $e->getMessage());
        }
    }

    private function testEmailSending(SymfonyStyle $io): void
    {
        try {
            $testEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'test@example.com';
            
            $email = (new Email())
                ->from($_ENV['MAIL_FROM_ADDRESS'] ?? 'contact@binajia.org')
                ->to($testEmail)
                ->subject('Test BINAJIA - Configuration Email')
                ->text('Ceci est un email de test pour vérifier la configuration SMTP de BINAJIA.')
                ->html('<p>Ceci est un <strong>email de test</strong> pour vérifier la configuration SMTP de BINAJIA.</p>');

            $this->mailer->send($email);
            $io->success("✅ Email de test envoyé avec succès à $testEmail");
            
        } catch (\Exception $e) {
            $io->error("❌ Erreur d'envoi d'email: " . $e->getMessage());
            $io->note("Vérifiez votre configuration SMTP dans le fichier .env");
        }
    }
}
