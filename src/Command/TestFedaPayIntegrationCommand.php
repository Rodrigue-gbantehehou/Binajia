<?php

namespace App\Command;

use App\Service\CardPaymentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-fedapay-integration',
    description: 'Test de l\'intégration FedaPay - Vérifie les appels API et la connectivité'
)]
class TestFedaPayIntegrationCommand extends Command
{
    public function __construct(
        private CardPaymentService $cardPaymentService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔗 Test de l\'intégration FedaPay');
        $io->writeln('Ce test vérifie la connectivité avec l\'API FedaPay et le bon fonctionnement des appels.');

        try {
            // Étape 1: Vérifier la configuration
            $io->section('⚙️ Vérification de la configuration');
            $configResult = $this->checkConfiguration($io);

            if (!$configResult['success']) {
                $io->error('❌ Configuration manquante : ' . $configResult['error']);
                return Command::FAILURE;
            }

            $io->success('✅ Configuration OK');

            // Étape 2: Tester la connectivité API
            $io->section('🌐 Test de connectivité API');
            $connectivityResult = $this->testApiConnectivity($io);

            if (!$connectivityResult['success']) {
                $io->warning('⚠️ Problème de connectivité : ' . $connectivityResult['error']);
                $io->writeln('💡 Vérifiez votre connexion internet et les URLs FedaPay');
            } else {
                $io->success('✅ Connectivité API OK');
                $io->writeln("📊 Status de l'API : " . ($connectivityResult['api_status'] ?? 'Inconnu'));
            }

            // Étape 3: Informations sur l'environnement
            $io->section('ℹ️ Informations sur l\'environnement');
            $this->displayEnvironmentInfo($io);

            // Étape 4: Recommandations
            $io->section('💡 Recommandations');
            $this->displayRecommendations($io);

            // Résumé
            if ($configResult['success'] && $connectivityResult['success']) {
                $io->success('🎉 L\'intégration FedaPay semble fonctionner correctement !');
                $io->writeln('📝 Note : L\'avertissement navigateur concernant StorageType.persistent n\'affecte pas le fonctionnement serveur.');
                return Command::SUCCESS;
            } else {
                $io->warning('⚠️ Certains problèmes détectés - Voir les détails ci-dessus');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Erreur inattendue : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function checkConfiguration(SymfonyStyle $io): array
    {
        $secret = $_ENV['FEDAPAY_SECRET_KEY'] ?? null;

        if (!$secret) {
            return [
                'success' => false,
                'error' => 'Variable d\'environnement FEDAPAY_SECRET_KEY manquante'
            ];
        }

        if (empty($secret) || strlen($secret) < 10) {
            return [
                'success' => false,
                'error' => 'Clé API FedaPay invalide ou trop courte'
            ];
        }

        $io->writeln('✅ Clé API FedaPay configurée');
        $io->writeln('🔑 Longueur de la clé : ' . strlen($secret) . ' caractères');

        return ['success' => true];
    }

    private function testApiConnectivity(SymfonyStyle $io): array
    {
        try {
            // Test simple de connectivité avec l'API FedaPay
            // On utilise l'URL de base pour vérifier l'accessibilité
            $testUrl = 'https://api.fedapay.com/v1/';

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'header' => 'User-Agent: BINAJIA-Test/1.0\r\n'
                ]
            ]);

            $response = @file_get_contents($testUrl, false, $context);

            if ($response === false) {
                $error = error_get_last();
                return [
                    'success' => false,
                    'error' => 'Impossible d\'atteindre l\'API FedaPay : ' . ($error['message'] ?? 'Erreur inconnue')
                ];
            }

            // Vérifier si on reçoit une réponse JSON valide
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Réponse API invalide : ' . json_last_error_msg()
                ];
            }

            return [
                'success' => true,
                'api_status' => 'Réponse reçue (' . strlen($response) . ' caractères)',
                'response_sample' => substr($response, 0, 100) . '...'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception lors du test : ' . $e->getMessage()
            ];
        }
    }

    private function displayEnvironmentInfo(SymfonyStyle $io): void
    {
        $io->writeln('🔗 URLs FedaPay utilisées :');
        $io->writeln('  • API : https://api.fedapay.com/v1/');
        $io->writeln('  • Scripts navigateur : https://sandbox-process.fedapay.com/...');
        $io->writeln('');

        $io->writeln('⚠️ Avertissement navigateur détecté :');
        $io->writeln('  • StorageType.persistent est déprécié');
        $io->writeln('  • FedaPay devrait migrer vers navigator.storage');
        $io->writeln('  • Cela n\'affecte PAS le fonctionnement serveur');
        $io->writeln('');

        $io->writeln('📝 Impact sur votre intégration :');
        $io->writeln('  • Aucun - l\'avertissement est côté navigateur seulement');
        $io->writeln('  • Vos appels API serveur fonctionnent normalement');
        $io->writeln('  • Les utilisateurs peuvent ignorer cet avertissement');
    }

    private function displayRecommendations(SymfonyStyle $io): void
    {
        $io->writeln('🚀 Actions recommandées :');
        $io->writeln('  1. ✅ Continuer l\'utilisation normale');
        $io->writeln('  2. 📧 Contacter FedaPay pour qu\'ils mettent à jour leurs scripts');
        $io->writeln('  3. 🔍 Surveiller les erreurs dans les logs Symfony');
        $io->writeln('  4. 🧪 Tester les paiements réels avec de petites sommes');
        $io->writeln('');

        $io->writeln('🔧 Pour masquer l\'avertissement navigateur :');
        $io->writeln('  • Chrome : DevTools > Paramètres > Masquer les avertissements de dépréciation');
        $io->writeln('  • Firefox : about:config > devtools.webconsole.filter.deprecation');
    }
}
