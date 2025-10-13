<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordResetTokenRepository $tokenRepository,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailService $emailService
    ) {}

    public function createResetToken(string $email): ?PasswordResetToken
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            return null;
        }

        // Invalider les anciens tokens
        $this->tokenRepository->invalidateUserTokens($user);

        // Créer un nouveau token
        $token = new PasswordResetToken();
        $token->setUser($user);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function sendResetEmail(PasswordResetToken $token): bool
    {
        try {
            $this->emailService->sendPasswordResetEmail($token);
            return true;
        } catch (\Exception $e) {
            // Log l'erreur si nécessaire
            return false;
        }
    }

    public function validateToken(string $tokenString): ?PasswordResetToken
    {
        return $this->tokenRepository->findValidTokenByToken($tokenString);
    }

    public function resetPassword(PasswordResetToken $token, string $newPassword): bool
    {
        if (!$token->isValid()) {
            return false;
        }

        $user = $token->getUser();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        // Marquer le token comme utilisé
        $token->setIsUsed(true);

        $this->entityManager->flush();

        return true;
    }

    public function cleanExpiredTokens(): int
    {
        return $this->tokenRepository->cleanExpiredTokens();
    }
}
