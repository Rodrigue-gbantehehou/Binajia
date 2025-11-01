<?php

namespace App\Controller\Admin;

use App\Entity\Projets;
use App\Form\ProjetsType;
use App\Repository\ProjetsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/projets')]
final class ProjetsController extends AbstractController
{
    #[Route(name: 'admin_projets_index', methods: ['GET'])]
    public function index(ProjetsRepository $projetsRepository): Response
    {
        return $this->render('admin/projets/index.html.twig', [
            'projets' => $projetsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_projets_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $projet = new Projets();
        $form = $this->createForm(ProjetsType::class, $projet);
        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('logos')->getData();
            if ($file) {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                //creer le dossier uploads/projets si il n'existe pas
                if (!file_exists($this->getParameter('kernel.project_dir') . '/var/uploads/projets')) {
                    mkdir($this->getParameter('kernel.project_dir') . '/var/uploads/projets', 0777, true);
                }
                $file->move(
                    $this->getParameter('kernel.project_dir') . '/var/uploads/projets',
                    $fileName
                );
                $path = 'projets/' . $fileName;
                $projet->setLogos($path);
            }

           
            $entityManager->persist($projet);
            $entityManager->flush();

            return $this->redirectToRoute('admin_projets_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/projets/new.html.twig', [
            'projet' => $projet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_projets_show', methods: ['GET'])]
    public function show(Projets $projet): Response
    {
        return $this->render('admin/projets/show.html.twig', [
            'projet' => $projet,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_projets_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Projets $projet, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjetsType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_projets_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/projets/edit.html.twig', [
            'projet' => $projet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_projets_delete', methods: ['POST'])]
    public function delete(Request $request, Projets $projet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$projet->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($projet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_projets_index', [], Response::HTTP_SEE_OTHER);
    }
}
