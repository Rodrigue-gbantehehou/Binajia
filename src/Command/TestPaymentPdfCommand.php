<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Payments;
use App\Service\EmailService;
use App\Entity\MembershipCards;
use App\Service\CardPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-payment-pdf',
    description: 'Test la génération de PDF après paiement'
)]
class TestPaymentPdfCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private CardPaymentService $cardPaymentService,
        private EmailService $emailService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test génération PDF après paiement');

        try {
            // Créer des données de test
            $user = $this->createTestUser();
            $card = $this->createTestCard($user);
            $payment = $this->createTestPayment($user);

            $io->writeln("✅ Données de test créées");

            // Simuler le callback de paiement (simuler un paiement approuvé)
            $io->section('💳 Simulation callback paiement');

            // D'abord marquer le paiement comme approved dans la base
            $payment->setStatus('completed');
            $this->em->flush();

            // Maintenant simuler la vérification
            $result = $this->simulatePaymentVerification($payment);

            if ($result['success']) {
                $io->success('✅ PDFs générés avec succès après paiement');
                $io->writeln("📄 Status: {$result['status']}");
                $io->writeln("📧 Message: {$result['message']}");
            } else {
                $io->error('❌ Échec génération PDFs: ' . $result['error']);
                return Command::FAILURE;
            }

            // Nettoyer
            $this->cleanup($user, $card, $payment);
            $io->writeln("🧹 Données de test nettoyées");

        } catch (\Exception $e) {
            $io->error('❌ Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test-payment-' . time() . '@example.com');
        $user->setFirstname('Test');
        $user->setLastname('Payment');
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
        $card->setCardnumberC('TEST-' . $user->getId());
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
        $payment->setStatus('pending');
        $payment->setReference('TEST-PAY-' . time());
        $payment->setPaymentMethod('test');
        $payment->setPaymentdate(new \DateTime());

        $this->em->persist($payment);
        $this->em->flush();

        return $payment;
    }

    private function cleanup(User $user, MembershipCards $card, Payments $payment): void
    {
        $this->em->remove($card);
        $this->em->remove($payment);
        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * Génère les PDFs via le service MembershipCardService
     */
    private function generateCardAndReceipt(User $user, Payments $payment, MembershipCards $card, ?string $avatarPath, string $memberId): ?array
    {
        // Utiliser le MembershipCardService injecté dans CardPaymentService
        // On va créer une instance temporaire pour cette commande
        $membershipCardService = new \App\Service\MembershipCardService(
            new \App\Service\PdfGeneratorService(
                new \Twig\Environment(new \Twig\Loader\FilesystemLoader($this->getProjectDir() . '/templates')),
                $this->getProjectDir() . '/var/uploads'
            ),
            $this->em,
            $this->getProjectDir() . '/var/uploads',
            new \App\Service\QrCodeService($this->getProjectDir() . '/var/uploads/private')
        );

        return $membershipCardService->generateAndPersist($user, $payment, $avatarPath, $memberId);
    }

    private function getProjectDir(): string
    {
        return __DIR__ . '/../..';
    }

    /**
     * Simule la vérification d'un paiement sans utiliser l'API FedaPay
     */
    private function simulatePaymentVerification(Payments $payment): array
    {
        try {
            // Récupérer la carte de l'utilisateur
            $card = $this->em->getRepository(MembershipCards::class)->findOneBy(['user' => $payment->getUser()]);

            if (!$card) {
                return ['success' => false, 'error' => 'Carte introuvable'];
            }

            // Activer la carte
            $card->setStatus(true);
            $this->em->flush();

            // Générer les PDFs
            $user = $payment->getUser();
            $memberId = $card->getCardnumberC();
            $avatarPath = $user->getAvatar() ?: null;

            $pdfPaths = $this->generateCardAndReceipt($user, $payment, $card, $avatarPath, $memberId);

            if ($pdfPaths && $pdfPaths['cardPdfPath']) {
                $card->setPdfurl($pdfPaths['cardPdfPath']);
                $this->em->flush();

                // Envoyer les emails
                $this->emailService->sendPaymentConfirmationEmail(
                    $user->getEmail(),
                    $user->getFirstname(),
                    $payment->getReference(),
                    (float) $payment->getAmount()
                );

                if ($card) {
                    $this->emailService->sendCardCreatedEmail(
                        $user->getEmail(),
                        $user->getFirstname(),
                        $card->getCardnumberC(),
                        $card->getPdfurl()
                    );

                    // Envoyer le reçu par email avec pièce jointe
                    if ($pdfPaths['receiptPdfPath']) {
                        $this->emailService->sendReceiptEmail(
                            $user->getEmail(),
                            $user->getFirstname(),
                            $payment,
                            $pdfPaths['receiptPdfPath']
                        );
                    }
                }

                return [
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Paiement confirmé et carte activée avec PDFs'
                ];
            }

            return ['success' => false, 'error' => 'Échec génération PDFs'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
