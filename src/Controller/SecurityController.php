<?php

namespace App\Controller;

use App\Document\User;
use App\Form\RegistrationFormType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        DocumentManager $dm,
        FileUploader $fileUploader
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('blog_list');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Обработка аватара
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatarFile')->getData();

            if ($avatarFile) {
                try {
                    $avatarFileName = $fileUploader->upload($avatarFile);
                    $user->setAvatar($avatarFileName);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Не удалось загрузить аватар, но регистрация прошла успешно.');
                }
            }

            $dm->persist($user);
            $dm->flush();

            $this->addFlash('success', 'Регистрация прошла успешно! Теперь вы можете войти.');

            return $this->redirectToRoute('login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('blog_list');
        }

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
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}