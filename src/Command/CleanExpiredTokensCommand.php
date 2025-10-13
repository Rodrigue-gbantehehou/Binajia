<?php

namespace App\Command;

use App\Service\PasswordResetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-expired-tokens',
    description: 'Nettoie les tokens de réinitialisation de mot de passe expirés'
)]
class CleanExpiredTokensCommand extends Command
{
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des tokens expirés');

        try {
            $deletedCount = $this->passwordResetService->cleanExpiredTokens();
            
            if ($deletedCount > 0) {
                $io->success(sprintf('%d token(s) expiré(s) supprimé(s)', $deletedCount));
            } else {
                $io->info('Aucun token expiré trouvé');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
