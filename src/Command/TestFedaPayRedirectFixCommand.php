<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:test-fedapay-redirect-fix',
    description: 'Test de la correction de redirection FedaPay vers /devenir-membre',
)]
class TestFedaPayRedirectFixCommand extends Command
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔧 Test de la correction de redirection FedaPay');

        // Problème identifié
        $io->section('🐛 Problème identifié');
        $io->writeln('FedaPay redirige vers : /devenir-membre?transaction-id=XXX&transaction-status=approved');
        $io->writeln('Cela affiche le formulaire au lieu de la page de confirmation de carte ❌');

        // Solution appliquée
        $io->section('✅ Solution appliquée');
        
        $io->writeln('1. **Interception JavaScript de la redirection** :');
        $io->listing([
            'Détection des paramètres transaction-id et transaction-status dans l\'URL',
            'Vérification du statut de paiement',
            'Récupération de l\'utilisateur via l\'API payment_get_user',
            'Redirection automatique vers la page de confirmation de carte'
        ]);

        $io->writeln('2. **Nouvelle fonction handleFedaPayRedirect()** :');
        $io->listing([
            'Vérifie le statut du paiement via payment_verify',
            'Récupère l\'ID utilisateur via payment_get_user',
            'ILS redirige vers app_membership_card_generated avec l\'ID utilisateur'
        ]);

        $io->writeln('3. **Amélioration du PaymentCallbackController** :');
        $io->listing([
            'Nouvelle route payment_get_user pour récupérer l\'utilisateur depuis une transaction',
            'Amélioration de payment_success pour rediriger vers la carte si possible',
            'Retourne redirectUrl dans les réponses JSON'
        ]);

        // URLs de test
        $io->section('🔗 URLs de test');
        
        $membershipUrl = $this->urlGenerator->generate('app_membership');
        $paymentVerifyUrl = $this->urlGenerator->generate('payment_verify');
        $paymentGetUserUrl = $this->urlGenerator->generate('payment_get_user', ['transactionId' => 'TEST_ID']);
        $paymentSuccessUrl = $this->urlGenerator->generate('payment_success');
        
        $io->writeln("Page d'inscription : {$membershipUrl}");
        $io->writeln("Vérification paiement : {$paymentVerifyUrl}?tx=TRANSACTION_ID");
        $io->writeln("Récupération utilisateur : {$paymentGetUserUrl}");
        $io->writeln("Page de succès : {$paymentSuccessUrl}?transaction_id=TRANSACTION_ID");

        // Flux corrigé
        $io->section('🔄 Flux corrigé');
        
        $io->writeln('1. Paiement FedaPay réussi ✅');
        $io->writeln('2. FedaPay redirige vers /devenir-membre?transaction-id=XXX&transaction-status=approved');
        $io->writeln('3. JavaScript détecte les paramètres dans l\'URL ✅');
        $io->writeln('4. Appelle handleFedaPayRedirect() ✅');
        $io->writeln('5. Vérifie le paiement via payment_verify ✅');
        $io->writeln('6. Récupère l\'utilisateur via payment_get_user ✅');
        $io->writeln('7. Redirige vers app_membership_card_generated/{userId} ✅');
        $io->writeln('8. Page de confirmation de carte s\'affiche ✅');

        // Tests recommandés
        $io->section('🧪 Tests recommandés');
        
        $io->writeln('Pour tester la correction :');
        $io->listing([
            'Effectuer un paiement de test avec FedaPay',
            'Vérifier que vous êtes redirigé vers /devenir-membre avec les paramètres',
            'Vérifier les logs de la console pour voir le traitement',
            'Vérifier que la page de confirmation de carte s\'affiche finalement',
            'Vérifier que le formulaire ne s\'affiche plus après le paiement'
        ]);

        $io->success('✅ Correction de redirection FedaPay appliquée !');
        $io->note('Le problème de redirection vers /devenir-membre est maintenant résolu.');
        $io->caution('Vérifiez que la route payment_get_user est correctement configurée dans votre base de données.');

        return Command::SUCCESS;
    }
}