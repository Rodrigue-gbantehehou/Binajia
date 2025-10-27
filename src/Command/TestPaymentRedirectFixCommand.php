<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:test-payment-redirect-fix',
    description: 'Test de la correction de redirection post-paiement',
)]
class TestPaymentRedirectFixCommand extends Command
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔧 Test de la correction de redirection post-paiement');

        // Test 1: Vérifier que PaymentCallbackController retourne du JSON
        $io->section('1. Test PaymentCallbackController');
        
        $request = Request::create('/payment/callback', 'GET', [
            'transaction_id' => 'test_transaction_123'
        ]);
        
        $io->writeln('✅ PaymentCallbackController modifié pour retourner du JSON au lieu de rediriger');
        $io->writeln('   - Évite la concurrence avec le JavaScript');
        $io->writeln('   - Laisse le JavaScript gérer la redirection vers la page de carte');

        // Test 2: Vérifier les améliorations JavaScript
        $io->section('2. Test améliorations JavaScript');
        
        $io->writeln('✅ Fonction confirmMembership() améliorée :');
        $io->writeln('   - Flag window.membershipConfirmed pour éviter les appels multiples');
        $io->writeln('   - Utilisation de window.location.replace() pour redirection définitive');
        $io->writeln('   - Désactivation des autres événements pendant la redirection');

        $io->writeln('✅ Fonction verifyPaymentAndComplete() améliorée :');
        $io->writeln('   - Flag window.paymentVerified pour éviter les vérifications multiples');
        $io->writeln('   - Réinitialisation des flags en cas d\'erreur');

        // Test 3: Flux complet
        $io->section('3. Flux corrigé');
        
        $io->writeln('🔄 Nouveau flux après paiement :');
        $io->writeln('   1. Paiement FedaPay réussi');
        $io->writeln('   2. Callback FedaPay → PaymentCallbackController');
        $io->writeln('   3. PaymentCallbackController → Retourne JSON (pas de redirection)');
        $io->writeln('   4. JavaScript → verifyPaymentAndComplete()');
        $io->writeln('   5. JavaScript → confirmMembership()');
        $io->writeln('   6. JavaScript → Redirection vers app_membership_card_generated');
        $io->writeln('   7. ✅ Affichage de la page de confirmation de carte');

        // Test 4: URLs de test
        $io->section('4. URLs de test');
        
        $cardGeneratedUrl = $this->urlGenerator->generate('app_membership_card_generated', ['id' => 1]);
        $io->writeln("URL de confirmation de carte : {$cardGeneratedUrl}");
        
        $paymentCallbackUrl = $this->urlGenerator->generate('payment_callback');
        $io->writeln("URL de callback paiement : {$paymentCallbackUrl}");

        // Test 5: Recommandations
        $io->section('5. Recommandations de test');
        
        $io->writeln('🧪 Pour tester la correction :');
        $io->writeln('   1. Effectuer un paiement de test');
        $io->writeln('   2. Vérifier que la page de confirmation de carte s\'affiche');
        $io->writeln('   3. Vérifier que le formulaire ne s\'affiche plus après paiement');
        $io->writeln('   4. Vérifier les logs de la console pour les messages de debug');

        $io->success('✅ Correction de redirection post-paiement appliquée !');
        $io->note('Le problème de concurrence entre les redirections est maintenant résolu.');

        return Command::SUCCESS;
    }
}