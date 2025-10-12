<?php

namespace App\Command;

use App\Entity\Culturalcontent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-demo-data',
    description: 'Crée des données de démonstration pour la présentation'
)]
class CreateDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si des données existent déjà
        $existingPlaces = $this->entityManager->getRepository(Culturalcontent::class)->findBy(['type' => 'lieu']);

        if (count($existingPlaces) > 0) {
            $io->warning('Des données de démonstration existent déjà. Utilisez --force pour les remplacer.');
            return Command::SUCCESS;
        }

        $io->info('Création des données de démonstration...');

        // Données de démonstration pour les lieux culturels
        $demoPlaces = [
            [
                'title' => 'Porte du Non-Retour',
                'description' => 'Site historique emblématique de Ouidah, témoin de l\'histoire de la traite négrière au Bénin.',
                'country' => 'Bénin',
                'type' => 'lieu',
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?q=80&w=800&auto=format&fit=crop'
            ],
            [
                'title' => 'Musée National de Lagos',
                'description' => 'Le plus ancien musée du Nigéria, abritant une collection exceptionnelle d\'art et d\'artisanat traditionnel.',
                'country' => 'Nigéria',
                'type' => 'lieu',
                'image' => 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?q=80&w=1200&auto=format&fit=crop'
            ],
            [
                'title' => 'Palais Royal d\'Abomey',
                'description' => 'Ancien palais des rois du Dahomey, classé au patrimoine mondial de l\'UNESCO.',
                'country' => 'Bénin',
                'type' => 'lieu',
                'image' => 'https://images.unsplash.com/photo-1523805009345-7448845a9e53?q=80&w=1200&auto=format&fit=crop'
            ],
            [
                'title' => 'Centre Culturel National Nigérian',
                'description' => 'Espace dédié à la préservation et à la promotion de la culture nigériane traditionnelle et contemporaine.',
                'country' => 'Nigéria',
                'type' => 'lieu',
                'image' => 'https://images.unsplash.com/photo-1590736969955-71cc94901144?q=80&w=1200&auto=format&fit=crop'
            ]
        ];

        foreach ($demoPlaces as $placeData) {
            $place = new Culturalcontent();
            $place->setTitle($placeData['title']);
            $place->setDescription($placeData['description']);
            $place->setCountry($placeData['country']);
            $place->setType($placeData['type']);
            $place->setImage($placeData['image']);
            $place->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($place);
        }

        $this->entityManager->flush();

        $io->success('Données de démonstration créées avec succès!');

        return Command::SUCCESS;
    }
}
