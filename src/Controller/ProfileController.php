<?php

namespace App\Controller;

use App\Document\User;
use App\Form\ProfileEditType;
use App\Service\FileUploader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'profile_view', methods: ['GET'])]
    public function view(): Response
    {
        return $this->render('profile/view.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обработка нового аватара
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatarFile')->getData();

            if ($avatarFile) {
                try {
                    // Удаляем старый аватар, если есть
                    if ($user->getAvatar()) {
                        $oldAvatarPath = $fileUploader->getAvatarsDirectory() . '/' . $user->getAvatar();
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }

                    // Загружаем новый
                    $avatarFileName = $fileUploader->upload($avatarFile);
                    $user->setAvatar($avatarFileName);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Не удалось загрузить аватар: ' . $e->getMessage());
                }
            }

            $dm->flush();

            $this->addFlash('success', 'Профиль успешно обновлён!');

            return $this->redirectToRoute('profile_view');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/avatar/delete', name: 'profile_avatar_delete', methods: ['POST'])]
    public function deleteAvatar(Request $request, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getAvatar()) {
            // Удаляем файл
            $avatarPath = $fileUploader->getAvatarsDirectory() . '/' . $user->getAvatar();
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }

            // Удаляем из базы
            $user->setAvatar(null);
            $dm->flush();

            $this->addFlash('success', 'Аватар удалён!');
        }

        return $this->redirectToRoute('profile_edit');
    }
}