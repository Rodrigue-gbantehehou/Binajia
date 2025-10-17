<?php
namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class MembersController extends AbstractController
{
    #[Route('/admin/members', name: 'admin_members_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q       = trim((string) $request->query->get('q', ''));
        $country = trim((string) $request->query->get('country', ''));
        $role    = trim((string) $request->query->get('role', ''));
        $page    = max(1, (int) $request->query->get('page', 1));
        $size    = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset  = ($page - 1) * $size;

        $qb = $em->getRepository(User::class)->createQueryBuilder('u');
        $conditions = [];
        if ($q !== '') {
            $conditions[] = '(LOWER(u.firstname) LIKE :q OR LOWER(u.lastname) LIKE :q OR LOWER(u.email) LIKE :q)';
        }
        if ($country !== '') {
            $conditions[] = 'LOWER(u.country) = :country';
        }
        if ($role !== '') {
            // roles stored as json array, use LIKE simple filter
            $conditions[] = 'u.roles LIKE :role';
        }
        if ($conditions) {
            $qb->where(implode(' AND ', $conditions));
        }
        if ($q !== '') { $qb->setParameter('q', '%'.strtolower($q).'%'); }
        if ($country !== '') { $qb->setParameter('country', strtolower($country)); }
        if ($role !== '') { $qb->setParameter('role', '%"'.strtoupper($role).'"%'); }

        $qbCount = clone $qb;
        $total = (int) ($qbCount->select('COUNT(u.id)')->getQuery()->getSingleScalarResult() ?? 0);

        $members = $qb
            ->select('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();

        $pages = (int) ceil(max(1, $total) / $size);

        return $this->render('admin/members/index.html.twig', [
            'members' => $members,
            'q' => $q,
            'country' => $country,
            'role' => $role,
            'page' => $page,
            'size' => $size,
            'total' => $total,
            'pages' => $pages,
        ]);
    }

    #[Route('/admin/members/{id}', name: 'admin_members_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Membre introuvable');
        }

        return $this->render('admin/members/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/members/{id}/edit', name: 'admin_members_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Membre introuvable');
        }

        if ($request->isMethod('POST')) {
            // Handle form submission without Symfony forms
            $firstname = trim($request->request->get('firstname', ''));
            $lastname = trim($request->request->get('lastname', ''));
            $email = trim($request->request->get('email', ''));
            $phone = trim($request->request->get('phone', ''));
            $country = trim($request->request->get('country', ''));
            $roles = $request->request->all('roles');

            // Validate required fields
            if (empty($firstname) || empty($lastname) || empty($email)) {
                $this->addFlash('error', 'Les champs prénom, nom et email sont obligatoires.');
                return $this->render('admin/members/edit.html.twig', [
                    'user' => $user,
                ]);
            }

            // Check if email is already used by another user
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre membre.');
                return $this->render('admin/members/edit.html.twig', [
                    'user' => $user,
                ]);
            }

            // Update user
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setPhone($phone);
            $user->setCountry($country);

            // Handle roles
            if (!empty($roles) && is_array($roles)) {
                $user->setRoles($roles);
            }

            $em->flush();

            $this->addFlash('success', 'Membre modifié avec succès.');
            return $this->redirectToRoute('admin_members_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/members/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/members/{id}/delete', name: 'admin_members_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Membre introuvable');
        }

        // Check if this is a POST request (CSRF protection)
        if (!$request->isMethod('POST')) {
            throw $this->createAccessDeniedException('Méthode non autorisée');
        }

        // Prevent deletion of the current user
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_members_index');
        }

        // Prevent deletion of admin users (optional security measure)
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer un administrateur.');
            return $this->redirectToRoute('admin_members_index');
        }

        $userEmail = $user->getEmail();
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Le membre ' . $userEmail . ' a été supprimé avec succès.');
        return $this->redirectToRoute('admin_members_index');
    }
}
