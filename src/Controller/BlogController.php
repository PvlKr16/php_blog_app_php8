<?php

namespace App\Controller;

use App\Document\Blog;
use App\Document\Category;
use App\Form\BlogType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Document\Attachment;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/blog')]
class BlogController extends AbstractController
{
    #[Route('/', name: 'blog_list', methods: ['GET'])]
    public function index(DocumentManager $dm): Response
    {
        $user = $this->getUser();
        $allBlogs = $dm->getRepository(Blog::class)
            ->findBy([], ['createdAt' => 'DESC']);

        // Фильтруем блоги по правам доступа
        $blogs = [];
        foreach ($allBlogs as $blog) {
            if ($blog->canView($user)) {
                $blogs[] = $blog;
            }
        }

        return $this->render('blog/index.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[Route('/new', name: 'blog_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $blog = new Blog();
        $categories = $dm->getRepository(Category::class)->findAll();

        $form = $this->createForm(BlogType::class, $blog, [
            'categories' => $categories,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $blog->setAuthor($this->getUser());
            $blog->addParticipant($this->getUser());

            $dm->persist($blog);
            $dm->flush(); // Сохраняем блог сначала, чтобы получить ID

            // Обработка вложений
            /** @var UploadedFile[] $attachmentFiles */
            $attachmentFiles = $form->get('attachments')->getData();

            if ($attachmentFiles) {
                $uploadedCount = 0;
                $failedCount = 0;

                foreach ($attachmentFiles as $file) {
                    try {
                        $fileData = $fileUploader->uploadAttachment($file);

                        $attachment = new Attachment();
                        $attachment->setFilename($fileData['filename']);
                        $attachment->setOriginalFilename($fileData['originalFilename']);
                        $attachment->setMimeType($fileData['mimeType']);
                        $attachment->setFileSize($fileData['fileSize']);
                        $attachment->setBlog($blog);

                        $dm->persist($attachment);
                        $uploadedCount++;

                    } catch (\Exception $e) {
                        $failedCount++;
                        $errorMsg = sprintf(
                            'Не удалось загрузить файл "%s": %s',
                            $file->getClientOriginalName(),
                            $e->getMessage()
                        );
                        $this->addFlash('warning', $errorMsg);
                    }
                }

                try {
                    $dm->flush();

                    if ($uploadedCount > 0) {
                        $this->addFlash('success', "Загружено файлов: $uploadedCount");
                    }
                    if ($failedCount > 0) {
                        $this->addFlash('warning', "Не удалось загрузить файлов: $failedCount");
                    }

                } catch (\Exception $e) {
                    $this->addFlash('error', 'Ошибка сохранения вложений в базу: ' . $e->getMessage());
                }
            }

            $this->addFlash('success', 'Блог успешно создан!');

            return $this->redirectToRoute('blog_show', ['id' => $blog->getId()]);
        }

        return $this->render('blog/new.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'blog_show', methods: ['GET'])]
    public function show(Blog $blog, DocumentManager $dm): Response
    {
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        // Загружаем вложения
        $attachments = $dm->getRepository(Attachment::class)->findBy(
            ['blog' => $blog],
            ['uploadedAt' => 'ASC']
        );

        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
            'attachments' => $attachments,
        ]);
    }

    #[Route('/{id}/edit', name: 'blog_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Blog $blog, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        if ($blog->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Только автор может редактировать блог.');
        }

        $categories = $dm->getRepository(Category::class)->findAll();

        // Загружаем текущие вложения
        $existingAttachments = $dm->getRepository(Attachment::class)->findBy(['blog' => $blog]);

        $form = $this->createForm(BlogType::class, $blog, [
            'categories' => $categories,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $blog->addParticipant($blog->getAuthor());
            $blog->setUpdatedAt(new \DateTime());

            // Обработка новых вложений
            /** @var UploadedFile[] $attachmentFiles */
            $attachmentFiles = $form->get('attachments')->getData();

            if ($attachmentFiles) {
                foreach ($attachmentFiles as $file) {
                    try {
                        $fileData = $fileUploader->uploadAttachment($file);

                        $attachment = new Attachment();
                        $attachment->setFilename($fileData['filename']);
                        $attachment->setOriginalFilename($fileData['originalFilename']);
                        $attachment->setMimeType($fileData['mimeType']);
                        $attachment->setFileSize($fileData['fileSize']);
                        $attachment->setBlog($blog);

                        $dm->persist($attachment);
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Не удалось загрузить файл: ' . $file->getClientOriginalName());
                    }
                }
            }

            $dm->flush();

            $this->addFlash('success', 'Блог успешно обновлен!');

            return $this->redirectToRoute('blog_show', ['id' => $blog->getId()]);
        }

        return $this->render('blog/edit.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
            'existingAttachments' => $existingAttachments,
        ]);
    }

    #[Route('/{id}', name: 'blog_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Blog $blog, DocumentManager $dm): Response
    {
        // Проверка доступа
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        if ($blog->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Только автор может удалить блог.');
        }

        // Удаляем без CSRF проверки (защищены авторизацией и методом DELETE)
        $dm->remove($blog);
        $dm->flush();

        $this->addFlash('success', 'Блог успешно удален!');

        return $this->redirectToRoute('blog_list');
    }

    #[Route('/attachment/{id}/delete', name: 'attachment_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAttachment(Attachment $attachment, Request $request, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $blog = $attachment->getBlog();

        // Проверка прав
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        if ($blog->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Только автор может удалять вложения.');
        }

        // Удаляем файл
        try {
            $fileUploader->deleteAttachment($attachment->getFilename());
        } catch (\Exception $e) {
            // Игнорируем ошибки удаления файла
        }

        // Удаляем из базы
        $dm->remove($attachment);
        $dm->flush();

        $this->addFlash('success', 'Файл удалён!');

        return $this->redirectToRoute('blog_show', ['id' => $blog->getId()]);
    }

}
