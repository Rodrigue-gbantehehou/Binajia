<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Payments;
use App\Entity\MembershipCards;
use App\Service\EmailService;
use App\Service\MembershipCardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-pdf-direct',
    description: 'Test direct de génération de PDF sans CardPaymentService'
)]
class TestPdfDirectCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailService $emailService,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test direct génération PDF');

        try {
            // Créer des données de test
            $user = $this->createTestUser();
            $card = $this->createTestCard($user);
            $payment = $this->createTestPayment($user);

            $io->writeln("✅ Données de test créées");

            // Générer les PDFs directement
            $io->section('🎴 Génération directe des PDFs');
            $memberId = $card->getCardnumberC();
            $avatarPath = ''; // String vide au lieu de null

            // Créer le service MembershipCardService
            $membershipCardService = new MembershipCardService(
                new \App\Service\PdfGeneratorService(
                    new \Twig\Environment(new \Twig\Loader\FilesystemLoader($this->projectDir . '/templates')),
                    $this->projectDir . '/var/uploads'
                ),
                $this->em,
                $this->projectDir . '/var/uploads',
                new \App\Service\QrCodeService($this->projectDir . '/var/uploads/private')
            );

            $result = $membershipCardService->generateAndPersist($user, $payment, '', $memberId);

            if ($result && $result['cardPdfPath']) {
                $io->success('✅ PDFs générés avec succès');
                $io->writeln("🎴 Carte PDF : {$result['cardPdfPath']}");
                $io->writeln("📄 Reçu PDF : " . ($result['receiptPdfPath'] ?: 'Non généré'));

                // Tester l'envoi d'emails
                $io->section('📧 Test envoi emails');
                $this->sendEmails($user, $payment, $result, $io);

            } else {
                $io->error('❌ Échec génération PDFs');
                return Command::FAILURE;
            }

            // Nettoyer
            $this->cleanup($user, $card, $payment);
            $io->writeln("🧹 Données de test nettoyées");

        } catch (\Exception $e) {
            $io->error('❌ Erreur: ' . $e->getMessage());
            $io->writeln("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test-direct-' . time() . '@example.com');
        $user->setFirstname('TestDirect');
        $user->setLastname('User');
        $user->setPhone('+229 01 02 03 04');
        $user->setCountry('Bénin');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('testpass');
        $user->setCreatedAt(new \DateTime());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createTestCard(User $user): MembershipCards
    {
        $card = new MembershipCards();
        $card->setCardnumberC('DIRECT-' . $user->getId());
        $card->setIssuedate(new \DateTime());
        $card->setExpiryDate((new \DateTime())->modify('+1 year'));
        $card->setStatus(false);
        $card->setUser($user);

        $this->em->persist($card);
        $this->em->flush();

        return $card;
    }

    private function createTestPayment(User $user): Payments
    {
        $payment = new Payments();
        $payment->setUser($user);
        $payment->setAmount('5000');
        $payment->setStatus('completed');
        $payment->setReference('DIRECT-TEST-' . time());
        $payment->setPaymentMethod('test');
        $payment->setPaymentdate(new \DateTime());

        $this->em->persist($payment);
        $this->em->flush();

        return $payment;
    }

    private function sendEmails(User $user, Payments $payment, array $cardResult, SymfonyStyle $io): void
    {
        $firstName = $user->getFirstname();
        $email = $user->getEmail();

        // Email de confirmation de paiement
        $paymentResult = $this->emailService->sendPaymentConfirmationEmail(
            $email,
            $firstName,
            $payment->getReference(),
            (float) $payment->getAmount()
        );

        $io->writeln($paymentResult ? "✅ Email de confirmation envoyé" : "❌ Échec email de confirmation");

        // Email de carte créée - utiliser le chemin relatif depuis la BD
        $cardEmailResult = $this->emailService->sendCardCreatedEmail(
            $email,
            $firstName,
            'DIRECT-' . $user->getId(),
            $cardResult['cardPdfUrl'] ?: $cardResult['cardPdfPath']
        );

        $io->writeln($cardEmailResult ? "✅ Email de carte créée envoyé" : "❌ Échec email de carte");

        // Email de reçu avec pièce jointe - utiliser le chemin absolu pour la PJ mais le relatif pour l'URL
        if ($cardResult['receiptPdfPath']) {
            $receiptResult = $this->emailService->sendReceiptEmail(
                $email,
                $firstName,
                $payment,
                $cardResult['receiptPdfPath'] // Chemin absolu pour la pièce jointe
            );

            $io->writeln($receiptResult ? "✅ Email de reçu avec PJ envoyé" : "❌ Échec email de reçu");
        }
    }

    private function cleanup(User $user, MembershipCards $card, Payments $payment): void
    {
        $this->em->remove($card);
        $this->em->remove($payment);
        $this->em->remove($user);
        $this->em->flush();
    }
}
