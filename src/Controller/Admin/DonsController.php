<?php

namespace App\Controller\Admin;

use App\Entity\Don;
use App\Form\Don1Type;
use App\Repository\DonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/dons')]
final class DonsController extends AbstractController
{
    #[Route(name: 'admin_dons_index', methods: ['GET'])]
    public function index(DonRepository $donRepository): Response
    {
        return $this->render('admin/dons/index.html.twig', [
            'dons' => $donRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_dons_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $don = new Don();
        $form = $this->createForm(Don1Type::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $don->setCreatedAt(new \DateTimeImmutable());
            $don->setUpdatedAt(new \DateTimeImmutable());
            
            $don->setStatut('En attente');
            
            $entityManager->persist($don);
            $entityManager->flush();

            return $this->redirectToRoute('admin_dons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/dons/new.html.twig', [
            'don' => $don,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_dons_show', methods: ['GET'])]
    public function show(Don $don): Response
    {
        return $this->render('admin/dons/show.html.twig', [
            'don' => $don,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_dons_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Don $don, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Don1Type::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $don->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return $this->redirectToRoute('admin_dons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/dons/edit.html.twig', [
            'don' => $don,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_dons_delete', methods: ['POST'])]
    public function delete(Request $request, Don $don, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$don->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($don);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_dons_index', [], Response::HTTP_SEE_OTHER);
    }
}
