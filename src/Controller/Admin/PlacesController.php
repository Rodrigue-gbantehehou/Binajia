<?php
namespace App\Controller\Admin;

use App\Entity\Place;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PlacesController extends AbstractController
{
    #[Route('/admin/places', name: 'admin_places_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q      = trim((string) $request->query->get('q', ''));
        $page   = max(1, (int) $request->query->get('page', 1));
        $size   = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset = ($page - 1) * $size;

        $qb = $em->getRepository(Place::class)->createQueryBuilder('p');
        if ($q !== '') { $qb->where('LOWER(p.name) LIKE :q OR LOWER(p.city) LIKE :q OR LOWER(p.country) LIKE :q')->setParameter('q', '%'.strtolower($q).'%'); }
        $qbCount = clone $qb;
        $total = (int) ($qbCount->select('COUNT(p.id)')->getQuery()->getSingleScalarResult() ?? 0);
        $items = $qb->orderBy('p.name', 'ASC')->setFirstResult($offset)->setMaxResults($size)->getQuery()->getResult();
        $pages = (int) ceil(max(1, $total) / $size);

        return $this->render('admin/places/index.html.twig', [
            'places' => $items,
            'q' => $q,
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'pages' => $pages,
        ]);
    }

    #[Route('/admin/places/new', name: 'admin_places_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        if ($request->isMethod('POST')) {
            $pl = new Place();
            $pl->setName((string)$request->request->get('name'));
            $pl->setDescription($request->request->get('description'));
            $pl->setCountry($request->request->get('country'));
            $pl->setCity($request->request->get('city'));
            $pl->setSlug($request->request->get('name'));

            // Handle image upload
            $imageFile = $request->files->get('image');
            if ($imageFile && $imageFile->isValid()) {
                try {
                    $imagePath = $fileUploader->saveUploadedFile($imageFile, 'places');
                    $pl->setImage('places/' . basename($imagePath)); // Store path with places/ directory
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            $em->persist($pl);
            $em->flush();

            $this->addFlash('success', 'Lieu créé avec succès.');
            return $this->redirectToRoute('admin_places_index');
        }
        return $this->render('admin/places/form.html.twig', ['place' => null]);
    }

    #[Route('/admin/places/{id}/edit', name: 'admin_places_edit', methods: ['GET','POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $pl = $em->getRepository(Place::class)->find($id);
        if (!$pl) { throw $this->createNotFoundException('Lieu introuvable'); }
        if ($request->isMethod('POST')) {
            $pl->setName((string)$request->request->get('name'));
            $pl->setDescription($request->request->get('description'));
            $pl->setCountry($request->request->get('country'));
            $pl->setCity($request->request->get('city'));
            $pl->setSlug($request->request->get('name'));

            // Handle image upload
            $imageFile = $request->files->get('image');
            if ($imageFile && $imageFile->isValid()) {
                try {
                    $imagePath = $fileUploader->saveUploadedFile($imageFile, 'places');
                    $pl->setImage('places/' . basename($imagePath)); // Store path with places/ directory
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            // Handle image removal
            if ($request->request->get('remove_image') === '1') {
                $pl->setImage(null);
            }

            $em->flush();

            $this->addFlash('success', 'Lieu modifié avec succès.');
            return $this->redirectToRoute('admin_places_index');
        }
        return $this->render('admin/places/form.html.twig', ['place' => $pl]);
    }

    #[Route('/admin/places/{id}', name: 'admin_places_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $pl = $em->getRepository(Place::class)->find($id);
        if (!$pl) { throw $this->createNotFoundException('Lieu introuvable'); }
        return $this->render('admin/places/show.html.twig', ['place' => $pl]);
    }

    #[Route('/admin/places/{id}/delete', name: 'admin_places_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $pl = $em->getRepository(Place::class)->find($id);
        if (!$pl) {
            throw $this->createNotFoundException('Lieu introuvable');
        }

        // Check if this is a POST request (CSRF protection)
        if (!$request->isMethod('POST')) {
            throw $this->createAccessDeniedException('Méthode non autorisée');
        }

        // Prevent deletion of places that might be referenced elsewhere
        // You can add additional checks here if needed

        $placeName = $pl->getName();
        $em->remove($pl);
        $em->flush();

        $this->addFlash('success', 'Le lieu "' . $placeName . '" a été supprimé avec succès.');
        return $this->redirectToRoute('admin_places_index');
    }
}
