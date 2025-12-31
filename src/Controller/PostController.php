<?php

namespace App\Controller;

use App\Document\Attachment;
use App\Document\Blog;
use App\Document\Post;
use App\Form\PostType;
use App\Service\FileUploader;
use App\Service\NotificationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('/blog/{blogId}/new/ajax', name: 'post_new_ajax', methods: ['POST'])]
    public function newAjax(string $blogId, Request $request, DocumentManager $dm, FileUploader $fileUploader, NotificationService $notificationService): JsonResponse
    {
        try {
            if (!$this->getUser()) {
                return $this->json(['success' => false, 'error' => 'Необходима авторизация'], 401);
            }

            $blog = $dm->getRepository(Blog::class)->find($blogId);
            if (!$blog) {
                return $this->json(['success' => false, 'error' => 'Блог не найден'], 404);
            }

            if (!$blog->canView($this->getUser())) {
                return $this->json(['success' => false, 'error' => 'Нет доступа'], 403);
            }

            $title = $request->request->get('title', '');
            $content = $request->request->get('content', '');

            if (empty($title) && empty($content)) {
                return $this->json(['success' => false, 'error' => 'Заголовок или содержание обязательны'], 400);
            }

            $post = new Post();
            $post->setTitle($title ?: 'Без заголовка');
            $post->setContent($content);
            $post->setBlog($blog);
            $post->setAuthor($this->getUser());

            $dm->persist($post);
            $dm->flush();

            // Обработка вложений
            $attachmentFiles = $request->files->get('attachments', []);
            $attachments = [];

            if ($attachmentFiles && is_array($attachmentFiles)) {
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

                        // Добавляем в массив для возврата
                        $attachments[] = [
                            'filename' => $fileData['filename'],
                            'originalFilename' => $fileData['originalFilename'],
                            'url' => '/uploads/attachments/' . $fileData['filename']
                        ];

                    } catch (\Exception $e) {
                        // Игнорируем ошибки загрузки отдельных файлов
                    }
                }

                $dm->flush();
            }

            try {
                $notificationService->notifyBlogParticipants($blog, $this->getUser());
            } catch (\Exception $e) {
                // Игнорируем ошибки уведомлений
            }

            $createdAt = $post->getCreatedAt()->format('d M H:i');

            $months = [
                'Jan' => 'Янв', 'Feb' => 'Фев', 'Mar' => 'Мар', 'Apr' => 'Апр',
                'May' => 'Май', 'Jun' => 'Июн', 'Jul' => 'Июл', 'Aug' => 'Авг',
                'Sep' => 'Сен', 'Oct' => 'Окт', 'Nov' => 'Ноя', 'Dec' => 'Дек'
            ];
            $createdAt = str_replace(array_keys($months), array_values($months), $createdAt);

            return $this->json([
                'success' => true,
                'post' => [
                    'id' => $post->getId(),
                    'title' => $post->getTitle(),
                    'content' => $post->getContent(),
                    'createdAt' => $createdAt,
                    'author' => [
                        'username' => $this->getUser()->getUsername(),
                        'avatar' => $this->getUser()->getAvatar(),
                    ],
                    'attachments' => $attachments, // ДОБАВЛЕНО
                    'canEdit' => true,
                    'url' => $this->generateUrl('post_show', ['id' => $post->getId()]),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Ошибка сервера: ' . $e->getMessage()
            ], 500);
        }
    }
}