<?php

namespace App\Controller\Admin;

use App\Entity\CulturalContent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CulturalContentController extends AbstractController
{
    #[Route('/admin/cultural-content', name: 'admin_cultural_content_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $type = $request->query->get('type'); // 'photo', 'video', null
        $page = max(1, (int) $request->query->get('page', 1));
        $size = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset = ($page - 1) * $size;

        $qb = $em->getRepository(CulturalContent::class)->createQueryBuilder('c');

        if ($q !== '') {
            $qb->andWhere('c.title LIKE :q OR c.description LIKE :q OR c.country LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($type !== null && $type !== '') {
            $qb->andWhere('c.type = :type')
               ->setParameter('type', $type);
        }

        $qb->orderBy('c.createdAt', 'DESC');

        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        $contents = $qb->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();

        $pages = (int) ceil($total / $size);

        return $this->render('admin/cultural_content/index.html.twig', [
            'contents' => $contents,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'size' => $size,
            'q' => $q,
            'type' => $type,
        ]);
    }

    #[Route('/admin/cultural-content/{id}', name: 'admin_cultural_content_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $content = $em->getRepository(CulturalContent::class)->find($id);
        if (!$content) {
            throw $this->createNotFoundException('Contenu culturel introuvable');
        }

        return $this->render('admin/cultural_content/show.html.twig', [
            'content' => $content,
        ]);
    }

    #[Route('/admin/cultural-content/new', name: 'admin_cultural_content_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $content = new CulturalContent();
            $content->setTitle($request->request->get('title'));
            $content->setDescription($request->request->get('description'));
            $content->setType($request->request->get('type'));
            $content->setCountry($request->request->get('country'));
            $content->setVideoUrl($request->request->get('video_url'));
            $content->setCreatedAt(new \DateTimeImmutable());

            // Upload image
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $projectDir = (string) $this->getParameter('kernel.project_dir');
                $uploadsDir = $projectDir . '/public/media/cultural';
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0775, true);
                }

                $filename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadsDir, $filename);
                $content->setImage('cultural/' . $filename);
            }

            $em->persist($content);
            $em->flush();

            $this->addFlash('success', 'Contenu ajouté avec succès.');
            return $this->redirectToRoute('admin_cultural_content_show', ['id' => $content->getId()]);
        }

        return $this->render('admin/cultural_content/new.html.twig');
    }

    #[Route('/admin/cultural-content/{id}/edit', name: 'admin_cultural_content_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $content = $em->getRepository(CulturalContent::class)->find($id);
        if (!$content) {
            throw $this->createNotFoundException('Contenu culturel introuvable');
        }

        if ($request->isMethod('POST')) {
            $content->setTitle($request->request->get('title'));
            $content->setDescription($request->request->get('description'));
            $content->setType($request->request->get('type'));
            $content->setCountry($request->request->get('country'));
            $content->setVideoUrl($request->request->get('video_url'));

            // Upload new image if provided
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $projectDir = (string) $this->getParameter('kernel.project_dir');
                $uploadsDir = $projectDir . '/public/media/cultural';
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0775, true);
                }

                $filename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadsDir, $filename);
                $content->setImage('cultural/' . $filename);
            }

            $em->flush();

            $this->addFlash('success', 'Contenu modifié avec succès.');
            return $this->redirectToRoute('admin_cultural_content_show', ['id' => $content->getId()]);
        }

        return $this->render('admin/cultural_content/edit.html.twig', [
            'content' => $content,
        ]);
    }

    #[Route('/admin/cultural-content/{id}/delete', name: 'admin_cultural_content_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $content = $em->getRepository(CulturalContent::class)->find($id);
        if (!$content) {
            throw $this->createNotFoundException('Contenu culturel introuvable');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_content_' . $content->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }

        $em->remove($content);
        $em->flush();

        $this->addFlash('success', 'Contenu supprimé avec succès.');
        return $this->redirectToRoute('admin_cultural_content_index');
    }
}
