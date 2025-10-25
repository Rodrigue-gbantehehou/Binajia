<?php

namespace App\Command;

use App\Entity\MembershipCards;
use App\Entity\Receipts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-pdf-paths',
    description: 'Vérifie les chemins PDF stockés dans la base de données'
)]
class CheckPdfPathsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private string $uploadDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔍 Vérification des chemins PDF en base de données');

        // Vérifier les cartes
        $io->section('🎴 Cartes de membre');
        $cards = $this->em->getRepository(MembershipCards::class)->findAll();

        foreach ($cards as $card) {
            $pdfUrl = $card->getPdfurl();
            $io->writeln("Carte ID {$card->getId()}: {$pdfUrl}");

            // Vérifier si le fichier existe
            $fullPath = $this->uploadDir . $pdfUrl;
            if (file_exists($fullPath)) {
                $io->writeln("  ✅ Fichier existe ({$fullPath})");
                $io->writeln("  📏 Taille: " . filesize($fullPath) . " bytes");
            } else {
                $io->writeln("  ❌ Fichier manquant ({$fullPath})");
            }
        }

        // Vérifier les reçus
        $io->section('📄 Reçus');
        $receipts = $this->em->getRepository(Receipts::class)->findAll();

        foreach ($receipts as $receipt) {
            $pdfUrl = $receipt->getPdfurl();
            $io->writeln("Reçu {$receipt->getReceiptNumber()}: {$pdfUrl}");

            // Vérifier si le fichier existe
            $fullPath = $this->uploadDir . $pdfUrl;
            if (file_exists($fullPath)) {
                $io->writeln("  ✅ Fichier existe ({$fullPath})");
                $io->writeln("  📏 Taille: " . filesize($fullPath) . " bytes");
            } else {
                $io->writeln("  ❌ Fichier manquant ({$fullPath})");
            }
        }

        return Command::SUCCESS;
    }
}
