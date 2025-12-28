<?php

namespace App\Controller;

use App\Document\Attachment;
use App\Document\Blog;
use App\Document\Comment;
use App\Form\CommentType;
use App\Service\FileUploader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
#[IsGranted('ROLE_USER')]
class CommentController extends AbstractController
{
    #[Route('/blog/{blogId}/add', name: 'comment_add', methods: ['POST'])]
    public function add(string $blogId, Request $request, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $blog = $dm->getRepository(Blog::class)->find($blogId);

        if (!$blog) {
            throw $this->createNotFoundException('Блог не найден.');
        }

        // Проверка доступа
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        $comment = new Comment();
        $comment->setBlog($blog);
        $comment->setAuthor($this->getUser());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->persist($comment);
            $dm->flush();

            // Обработка вложений
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
                        $attachment->setComment($comment);

                        $dm->persist($attachment);
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Не удалось загрузить файл: ' . $file->getClientOriginalName());
                    }
                }
                $dm->flush();
            }

            $this->addFlash('success', 'Комментарий добавлен!');
        }

        return $this->redirectToRoute('blog_show', ['id' => $blogId]);
    }

    #[Route('/{commentId}/reply', name: 'comment_reply', methods: ['POST'])]
    public function reply(string $commentId, Request $request, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $parentComment = $dm->getRepository(Comment::class)->find($commentId);

        if (!$parentComment) {
            throw $this->createNotFoundException('Комментарий не найден.');
        }

        $blog = $parentComment->getBlog();

        // Проверка доступа
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        $comment = new Comment();
        $comment->setBlog($blog);
        $comment->setAuthor($this->getUser());
        $comment->setParentComment($parentComment);

        $form = $this->createForm(CommentType::class, $comment, ['is_reply' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->persist($comment);
            $dm->flush();

            // Обработка вложений
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
                        $attachment->setComment($comment);

                        $dm->persist($attachment);
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Не удалось загрузить файл: ' . $file->getClientOriginalName());
                    }
                }
                $dm->flush();
            }

            $this->addFlash('success', 'Ответ добавлен!');
        }

        return $this->redirectToRoute('blog_show', ['id' => $blog->getId()]);
    }

    #[Route('/{id}/delete', name: 'comment_delete', methods: ['DELETE'])]
    public function delete(Comment $comment, DocumentManager $dm, FileUploader $fileUploader): Response
    {
        $blog = $comment->getBlog();

        // Проверка прав - только автор комментария или автор блога
        if ($comment->getAuthor() !== $this->getUser() && $blog->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Вы не можете удалить этот комментарий.');
        }

        // Удаляем вложения комментария
        $attachments = $dm->getRepository(Attachment::class)->findBy(['comment' => $comment]);
        foreach ($attachments as $attachment) {
            try {
                $fileUploader->deleteAttachment($attachment->getFilename());
            } catch (\Exception $e) {
                // Игнорируем ошибки удаления файлов
            }
            $dm->remove($attachment);
        }

        $dm->remove($comment);
        $dm->flush();

        $this->addFlash('success', 'Комментарий удалён!');

        return $this->redirectToRoute('blog_show', ['id' => $blog->getId()]);
    }
}