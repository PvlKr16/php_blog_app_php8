<?php

namespace App\Controller;

use App\Document\Attachment;
use App\Document\Blog;
use App\Document\Post;
use App\Form\PostType;
use App\Service\FileUploader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/post')]
#[IsGranted('ROLE_USER')]
class PostController extends AbstractController
{
    #[Route('/blog/{blogId}/new', name: 'post_new', methods: ['GET', 'POST'])]
    public function new(string $blogId, Request $request, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $blog = $dm->getRepository(Blog::class)->find($blogId);

        if (!$blog) {
            throw $this->createNotFoundException('Блог не найден.');
        }

        // Проверка доступа - должен видеть блог
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        $post = new Post();
        $post->setBlog($blog);
        $post->setAuthor($this->getUser());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->persist($post);
            $dm->flush();

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
                        $attachment->setPost($post);

                        $dm->persist($attachment);
                        $uploadedCount++;

                    } catch (\Exception $e) {
                        $failedCount++;
                        $this->addFlash('warning', 'Не удалось загрузить файл: ' . $file->getClientOriginalName());
                    }
                }

                $dm->flush();

                if ($uploadedCount > 0) {
                    $this->addFlash('info', "Загружено файлов: $uploadedCount");
                }
            }

            $this->addFlash('success', 'Запись успешно добавлена!');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/new.html.twig', [
            'blog' => $blog,
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'post_show', methods: ['GET'])]
    public function show(Post $post, DocumentManager $dm): Response
    {
        $blog = $post->getBlog();

        // Проверка доступа
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этой записи.');
        }

        // Загружаем вложения
        $attachments = $dm->getRepository(Attachment::class)->findBy(
            ['post' => $post],
            ['uploadedAt' => 'ASC']
        );

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'blog' => $blog,
            'attachments' => $attachments,
        ]);
    }

    #[Route('/{id}/edit', name: 'post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $blog = $post->getBlog();

        // Проверка доступа
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этой записи.');
        }

        // Только автор может редактировать
        if ($post->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Только автор может редактировать запись.');
        }

        // Загружаем текущие вложения
        $existingAttachments = $dm->getRepository(Attachment::class)->findBy(['post' => $post]);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTime());

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
                        $attachment->setPost($post);

                        $dm->persist($attachment);
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Не удалось загрузить файл: ' . $file->getClientOriginalName());
                    }
                }
            }

            $dm->flush();

            $this->addFlash('success', 'Запись успешно обновлена!');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'blog' => $blog,
            'form' => $form->createView(),
            'existingAttachments' => $existingAttachments,
        ]);
    }

    #[Route('/{id}', name: 'post_delete', methods: ['DELETE'])]
    public function delete(Post $post, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $blog = $post->getBlog();

        // Проверка доступа
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этой записи.');
        }

        // Только автор может удалять
        if ($post->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Только автор может удалить запись.');
        }

        $blogId = $blog->getId();

        // Удаляем вложения записи
        $attachments = $dm->getRepository(Attachment::class)->findBy(['post' => $post]);
        foreach ($attachments as $attachment) {
            try {
                $fileUploader->deleteAttachment($attachment->getFilename());
            } catch (\Exception $e) {
                // Игнорируем ошибки удаления файлов
            }
            $dm->remove($attachment);
        }

        $dm->remove($post);
        $dm->flush();

        $this->addFlash('success', 'Запись удалена!');

        return $this->redirectToRoute('blog_show', ['id' => $blogId]);
    }
}