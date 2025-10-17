<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-fedapay-config',
    description: 'Test rapide de la configuration FedaPay - Vérification locale seulement'
)]
class TestFedaPayConfigCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔧 Test de configuration FedaPay (Local)');

        try {
            // Vérifier les variables d'environnement
            $io->section('⚙️ Variables d\'environnement');

            $secret = $_ENV['FEDAPAY_SECRET_KEY'] ?? null;
            $public = $_ENV['FEDAPAY_PUBLIC_KEY'] ?? null;

            if (!$secret) {
                $io->error('❌ FEDAPAY_SECRET_KEY manquante');
                return Command::FAILURE;
            }

            if (!$public) {
                $io->error('❌ FEDAPAY_PUBLIC_KEY manquante');
                return Command::FAILURE;
            }

            $io->success('✅ Variables d\'environnement configurées');
            $io->writeln("🔑 Clé publique : " . substr($public, 0, 20) . '...');
            $io->writeln("🔐 Clé secrète : " . substr($secret, 0, 20) . '...');

            // Vérifier le service CardPaymentService
            $io->section('🏗️ Vérification du service');

            if (!$this->getApplication()->getKernel()->getContainer()->has('App\Service\CardPaymentService')) {
                $io->error('❌ Service CardPaymentService non trouvé');
                return Command::FAILURE;
            }

            $io->success('✅ Service CardPaymentService disponible');

            // Informations sur l'intégration
            $io->section('ℹ️ Informations');
            $io->writeln('🔗 Endpoints utilisés :');
            $io->writeln('  • API : https://api.fedapay.com/v1/');
            $io->writeln('  • Sandbox scripts : https://sandbox-process.fedapay.com/');
            $io->writeln('');
            $io->writeln('⚠️ Avertissement navigateur :');
            $io->writeln('  • StorageType.persistent est déprécié côté navigateur');
            $io->writeln('  • Cela n\'affecte PAS le fonctionnement serveur');
            $io->writeln('  • FedaPay devrait mettre à jour leurs scripts');

            $io->success('🎉 Configuration FedaPay validée !');

        } catch (\Exception $e) {
            $io->error('❌ Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
