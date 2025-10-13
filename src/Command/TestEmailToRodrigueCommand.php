<?php

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email-rodrigue',
    description: 'Envoie un email de test à rodriguenagnon@gmail.com'
)]
class TestEmailToRodrigueCommand extends Command
{
    public function __construct(
        private EmailService $emailService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('📧 Test d\'envoi d\'email à Rodrigue - BINAJIA');

        try {
            // Test 1: Email de bienvenue
            $io->section('✉️ Envoi email de bienvenue');
            $result1 = $this->emailService->sendWelcomeEmail(
                'rodriguenagnon@gmail.com',
                'Rodrigue',
                'NAGNON',
                'TempPass2024!'
            );
            
            if ($result1) {
                $io->success('✅ Email de bienvenue envoyé avec succès !');
            } else {
                $io->error('❌ Échec envoi email de bienvenue');
            }

            // Test 2: Email de carte créée
            $io->section('🎴 Envoi email de carte créée');
            $result2 = $this->emailService->sendCardCreatedEmail(
                'rodriguenagnon@gmail.com',
                'Rodrigue',
                'BNJ001234',
                '/media/cards/test_card.pdf'
            );
            
            if ($result2) {
                $io->success('✅ Email de carte créée envoyé avec succès !');
            } else {
                $io->error('❌ Échec envoi email de carte');
            }

            // Test 3: Email de confirmation de paiement
            $io->section('💳 Envoi email de confirmation de paiement');
            $result3 = $this->emailService->sendPaymentConfirmationEmail(
                'rodriguenagnon@gmail.com',
                'Rodrigue',
                'PAY_' . date('YmdHis'),
                25000
            );
            
            if ($result3) {
                $io->success('✅ Email de confirmation de paiement envoyé avec succès !');
            } else {
                $io->error('❌ Échec envoi email de paiement');
            }

            $io->note('Vérifiez votre boîte email : rodriguenagnon@gmail.com');
            
            if ($result1 && $result2 && $result3) {
                $io->success('🎉 Tous les emails ont été envoyés avec succès !');
            } else {
                $io->warning('⚠️ Certains emails n\'ont pas pu être envoyés');
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur pendant l\'envoi : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
