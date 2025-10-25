<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\MembershipCards;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Service\MembershipCardService;

#[AsCommand(
    name: 'app:test-real-member-card',
    description: 'Test la génération de carte avec le vrai avatar enregistré du membre'
)]
class TestRealMemberCardCommand extends Command
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private EntityManagerInterface $em,
        private MembershipCardService $membershipCardService,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('userId', InputArgument::REQUIRED, 'ID de l\'utilisateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');

        $io->title('🎯 Test Carte Membre avec Vrai Avatar');

        // Récupérer l'utilisateur
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            $io->error("❌ Utilisateur avec ID $userId introuvable");
            return Command::FAILURE;
        }

        // Récupérer la carte du membre
        $card = $this->em->getRepository(MembershipCards::class)->findOneBy(['user' => $user]);
        if (!$card) {
            $io->error("❌ Carte membre introuvable pour l'utilisateur $userId");
            return Command::FAILURE;
        }

        $io->writeln("👤 Membre: {$user->getFirstname()} {$user->getLastname()}");
        $io->writeln("🆔 Member ID: {$card->getCardnumberC()}");
        $io->writeln("📸 Avatar en BD: {$card->getPhoto()}");

        // Préparer les données pour la carte
        $memberId = $card->getCardnumberC() ?? sprintf('BjNg-%s-%03d', date('Y'), $user->getId());
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));
        $phone = (string)($user->getPhone() ?? '');
        $nationality = (string)($user->getCountry() ?? '');

        $issuedAt = $card->getIssuedate() ?? new \DateTime();
        $expiryAt = $card->getExpiryDate() ?? (new \DateTime())->modify('+1 year');

        // Utiliser la nouvelle méthode qui récupère automatiquement le vrai avatar
        $result = $membershipCardService->generateCardWithExistingAvatar($card);

        if (isset($result['cardPdfPath']) && file_exists($result['cardPdfPath'])) {
            $io->success('✅ Carte membre avec vrai avatar générée');
            $io->writeln("📁 Fichier: {$result['cardPdfPath']}");
            $io->writeln("📏 Taille: " . filesize($result['cardPdfPath']) . " octets");

            if (filesize($result['cardPdfPath']) > 1000) {
                $io->success('✅ PDF généré avec du contenu');
            } else {
                $io->warning('⚠️ PDF généré mais très petit');
            }
        } else {
            $io->error('❌ Échec de la génération du PDF');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
