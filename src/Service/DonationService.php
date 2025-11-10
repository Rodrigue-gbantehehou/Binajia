<?php
// src/Service/DonationService.php

namespace App\Service;

use App\Entity\Don;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DonationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function createDonationFromRequest(Request $request): Don
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        
        // Validation des données
        if (empty($data['montant'])) {
            throw new BadRequestHttpException('Le montant est obligatoire');
        }

        $montant = floatval($data['montant']);
        if ($montant < 100) {
            throw new BadRequestHttpException('Le montant minimum est de 100 FCFA');
        }

        $don = new Don();
        $don->setMontant($montant);
        $don->setCreatedAt(new \DateTimeImmutable());
        $don->setUpdatedAt(new \DateTimeImmutable());
        
        // Gestion du don anonyme
        $isAnonymous = isset($data['is_anonymous']) && $data['is_anonymous'];
        $don->setIsanonymous($isAnonymous);
        
        if ($isAnonymous) {
            $don->setNom('Donateur Anonyme');
            $don->setEmail('anonyme@binajia.org');
        } else {
            if (empty($data['nom'])) {
                throw new BadRequestHttpException('Le nom est obligatoire pour un don non anonyme');
            }
            if (empty($data['email'])) {
                throw new BadRequestHttpException('L\'email est obligatoire pour un don non anonyme');
            }
            
            $don->setNom($data['nom']);
            $don->setEmail($data['email']);
        }

        $don->setTypeDon($data['typeDon'] ?? 'ponctuel');
        $don->setStatut('pending');

        $this->entityManager->persist($don);
        $this->entityManager->flush();

        return $don;
    }

    public function updateDonationStatus(int $donId, string $status, ?string $transactionId = null): void
    {
        $don = $this->entityManager->getRepository(Don::class)->find($donId);
        
        if (!$don) {
            throw new \RuntimeException('Don non trouvé');
        }

        $don->setStatut($status);
        if ($transactionId) {
            $don->setTransactionId($transactionId);
        }
        $don->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}