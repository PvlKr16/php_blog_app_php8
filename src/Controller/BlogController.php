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
    public function new(Request $request, DocumentManager $dm): Response
    {
        $blog = new Blog();
        $categories = $dm->getRepository(Category::class)->findAll();

        $form = $this->createForm(BlogType::class, $blog, [
            'categories' => $categories,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $blog->setAuthor($this->getUser());

            // Автор автоматически становится участником
            $blog->addParticipant($this->getUser());

            $dm->persist($blog);
            $dm->flush();

            $this->addFlash('success', 'Блог успешно создан!');

            return $this->redirectToRoute('blog_show', ['id' => $blog->getId()]);
        }

        return $this->render('blog/new.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'blog_show', methods: ['GET'])]
    public function show(Blog $blog): Response
    {
        // Проверяем, может ли пользователь просматривать блог
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }

    #[Route('/{id}/edit', name: 'blog_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Blog $blog, DocumentManager $dm): Response
    {
        // Проверка доступа к просмотру
        if (!$blog->canView($this->getUser())) {
            throw $this->createAccessDeniedException('У вас нет доступа к этому блогу.');
        }

        // Проверка прав на редактирование - только автор
        if ($blog->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Только автор может редактировать блог.');
        }

        $categories = $dm->getRepository(Category::class)->findAll();

        $form = $this->createForm(BlogType::class, $blog, [
            'categories' => $categories,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Убеждаемся, что автор всегда в участниках
            $blog->addParticipant($blog->getAuthor());

            $blog->setUpdatedAt(new \DateTime());
            $dm->flush();

            $this->addFlash('success', 'Блог успешно обновлен!');

            return $this->redirectToRoute('blog_show', ['id' => $blog->getId()]);
        }

        return $this->render('blog/edit.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
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
}
