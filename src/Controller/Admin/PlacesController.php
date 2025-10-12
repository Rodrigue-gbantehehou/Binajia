<?php
namespace App\Controller\Admin;

use App\Entity\Place;
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
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $pl = new Place();
            $pl->setName((string)$request->request->get('name'));
            $pl->setDescription($request->request->get('description'));
            $pl->setCountry($request->request->get('country'));
            $pl->setCity($request->request->get('city'));
            $em->persist($pl);
            $em->flush();
            return $this->redirectToRoute('admin_places_index');
        }
        return $this->render('admin/places/form.html.twig', ['place' => null]);
    }

    #[Route('/admin/places/{id}/edit', name: 'admin_places_edit', methods: ['GET','POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $pl = $em->getRepository(Place::class)->find($id);
        if (!$pl) { throw $this->createNotFoundException('Lieu introuvable'); }
        if ($request->isMethod('POST')) {
            $pl->setName((string)$request->request->get('name'));
            $pl->setDescription($request->request->get('description'));
            $pl->setCountry($request->request->get('country'));
            $pl->setCity($request->request->get('city'));
            $em->flush();
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
}
