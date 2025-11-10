<?php
namespace App\Controller\Admin;

use App\Entity\Evenement;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class EventsController extends AbstractController
{
    #[Route('/admin/events', name: 'admin_events_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q      = trim((string) $request->query->get('q', ''));
        $from   = $request->query->get('from');
        $to     = $request->query->get('to');
        $page   = max(1, (int) $request->query->get('page', 1));
        $size   = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset = ($page - 1) * $size;

        $qb = $em->getRepository(Evenement::class)->createQueryBuilder('e');
        $conds = [];
        if ($q !== '') { $conds[] = '(LOWER(e.title) LIKE :q OR LOWER(e.description) LIKE :q OR LOWER(e.location) LIKE :q)'; }
        if ($from) { $conds[] = 'e.startDate >= :from'; }
        if ($to) { $conds[] = 'e.startDate <= :to'; }
        if ($conds) { $qb->where(implode(' AND ', $conds)); }
        if ($q !== '') { $qb->setParameter('q', '%'.strtolower($q).'%'); }
        if ($from) { $qb->setParameter('from', new \DateTime($from.' 00:00:00')); }
        if ($to) { $qb->setParameter('to', new \DateTime($to.' 23:59:59')); }

        $qbCount = clone $qb;
        $total = (int) ($qbCount->select('COUNT(e.id)')->getQuery()->getSingleScalarResult() ?? 0);
        $items = $qb->orderBy('e.startDate', 'DESC')->setFirstResult($offset)->setMaxResults($size)->getQuery()->getResult();
        $pages = (int) ceil(max(1, $total) / $size);

        return $this->render('admin/events/index.html.twig', [
            'events' => $items,
            'q' => $q,
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'pages' => $pages,
        ]);
    }

    #[Route('/admin/events/new', name: 'admin_events_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        if ($request->isMethod('POST')) {
            $e = new Evenement();
            $e->setTitle((string) $request->request->get('title'));
            $e->setDescription($request->request->get('description'));
            $e->setLocation($request->request->get('location'));
            $e->setCountry($request->request->get('country'));
            $sd = $request->request->get('startDate');
            $ed = $request->request->get('endDate');
            $e->setStartDate($sd ? new \DateTime($sd) : null);
            $e->setEndDate($ed ? new \DateTime($ed) : null);
            $e->setIsOnline(true);

            // Handle image upload
            $imageFile = $request->files->get('image');
            if ($imageFile && $imageFile->isValid()) {
                try {
                    $imagePath = $fileUploader->saveUploadedFile($imageFile, 'events');
                    $e->setImage('events/' . basename($imagePath)); // Store path with events/ directory
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            // Handle image removal
            if ($request->request->get('remove_image') === '1') {
                $e->setImage(null);
            }

            $em->persist($e);
            $em->flush();

            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('admin_events_index');
        }
        return $this->render('admin/events/form.html.twig', [ 'event' => null ]);
    }

    #[Route('/admin/events/{id}/edit', name: 'admin_events_edit', methods: ['GET','POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $e = $em->getRepository(Evenement::class)->find($id);
        if (!$e) { throw $this->createNotFoundException('Événement introuvable'); }
        if ($request->isMethod('POST')) {
            $e->setTitle((string) $request->request->get('title'));
            $e->setDescription($request->request->get('description'));
            $e->setLocation($request->request->get('location'));
            $e->setCountry($request->request->get('country'));
            $sd = $request->request->get('startDate');
            $ed = $request->request->get('endDate');
            $e->setStartDate($sd ? new \DateTime($sd) : null);
            $e->setEndDate($ed ? new \DateTime($ed) : null);
            $e->setIsOnline(true);

            // Handle image upload
            $imageFile = $request->files->get('image');
            if ($imageFile && $imageFile->isValid()) {
                try {
                    $imagePath = $fileUploader->saveUploadedFile($imageFile, 'events');
                    $e->setImage('events/' . basename($imagePath)); // Store path with events/ directory
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            // Handle image removal
            if ($request->request->get('remove_image') === '1') {
                $e->setImage(null);
            }

            $em->flush();

            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('admin_events_index');
        }
        return $this->render('admin/events/form.html.twig', [ 'event' => $e ]);
    }

    #[Route('/admin/events/{id}', name: 'admin_events_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $e = $em->getRepository(Evenement::class)->find($id);
        if (!$e) { throw $this->createNotFoundException('Événement introuvable'); }
        return $this->render('admin/events/show.html.twig', [ 'event' => $e ]);
    }

    #[Route('/admin/events/{id}/delete', name: 'admin_events_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $e = $em->getRepository(Evenement::class)->find($id);
        if (!$e) {
            throw $this->createNotFoundException('Événement introuvable');
        }

        // Check if this is a POST request (CSRF protection)
        if (!$request->isMethod('POST')) {
            throw $this->createAccessDeniedException('Méthode non autorisée');
        }

        // Prevent deletion of events that might be referenced elsewhere
        // You can add additional checks here if needed

        $eventTitle = $e->getTitle() ?: 'Sans titre';
        $em->remove($e);
        $em->flush();

        $this->addFlash('success', 'L\'événement "' . $eventTitle . '" a été supprimé avec succès.');
        return $this->redirectToRoute('admin_events_index');
    }
}
