<?php
namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SecurityController extends AbstractController
{
    #[Route('/admin/security/users', name: 'admin_security_users', methods: ['GET'])]
    public function users(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC')->getQuery()->getResult();
        return $this->render('admin/security/users.html.twig', [ 'users' => $users ]);
    }

    #[Route('/admin/security/users/{id}/toggle-admin', name: 'admin_security_toggle_admin', methods: ['POST'])]
    public function toggleAdmin(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) { throw $this->createNotFoundException('Utilisateur introuvable'); }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_admin_'.$user->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);
        if ($isAdmin) {
            $roles = array_values(array_filter($roles, fn($r) => $r !== 'ROLE_ADMIN'));
        } else {
            $roles[] = 'ROLE_ADMIN';
        }
        // Remove implicit ROLE_USER duplicates
        $roles = array_values(array_unique(array_filter($roles, fn($r) => $r !== 'ROLE_USER')));
        $user->setRoles($roles);
        $em->flush();
        $this->addFlash('success', $isAdmin ? 'ROLE_ADMIN retiré.' : 'ROLE_ADMIN ajouté.');
        return $this->redirectToRoute('admin_security_users');
    }
}
