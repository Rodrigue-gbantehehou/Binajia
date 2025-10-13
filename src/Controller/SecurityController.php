<?php
namespace App\Controller;

use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Controller can be blank: it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, PasswordResetService $passwordResetService, ValidatorInterface $validator): Response
    {
        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            // Validation de l'email
            $violations = $validator->validate($email, [
                new Assert\NotBlank(['message' => 'L\'email ne peut pas être vide']),
                new Assert\Email(['message' => 'L\'email n\'est pas valide'])
            ]);

            if (count($violations) === 0) {
                $token = $passwordResetService->createResetToken($email);
                
                if ($token) {
                    $emailSent = $passwordResetService->sendResetEmail($token);
                    if ($emailSent) {
                        $success = true;
                    } else {
                        $error = 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.';
                    }
                } else {
                    // Pour des raisons de sécurité, on affiche le même message même si l'email n'existe pas
                    $success = true;
                }
            } else {
                $error = $violations[0]->getMessage();
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'error' => $error,
            'success' => $success
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(string $token, Request $request, PasswordResetService $passwordResetService, ValidatorInterface $validator): Response
    {
        $resetToken = $passwordResetService->validateToken($token);
        
        if (!$resetToken) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validation du mot de passe
            $violations = $validator->validate($password, [
                new Assert\NotBlank(['message' => 'Le mot de passe ne peut pas être vide']),
                new Assert\Length([
                    'min' => 6,
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                ])
            ]);

            if (count($violations) === 0) {
                if ($password === $confirmPassword) {
                    $success = $passwordResetService->resetPassword($resetToken, $password);
                    
                    if ($success) {
                        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.');
                        return $this->redirectToRoute('app_login');
                    } else {
                        $error = 'Erreur lors de la modification du mot de passe.';
                    }
                } else {
                    $error = 'Les mots de passe ne correspondent pas.';
                }
            } else {
                $error = $violations[0]->getMessage();
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'error' => $error
        ]);
    }
}
