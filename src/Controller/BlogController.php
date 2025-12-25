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
        $blogs = $dm->getRepository(Blog::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('blog/index.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/new', name: 'blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, DocumentManager $dm): Response
    {
        $blog = new Blog();
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $blog->setAuthor($this->getUser());
            
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
        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }

    #[Route('/{id}/edit', name: 'blog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Blog $blog, DocumentManager $dm): Response
    {
        // Проверка прав доступа - редактировать может только автор
        if ($blog->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Вы не можете редактировать чужой блог.');
        }

        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

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

    #[Route('/{id}/delete', name: 'blog_delete', methods: ['POST'])]
    public function delete(Request $request, Blog $blog, DocumentManager $dm): Response
    {
        // Проверка прав доступа - удалять может только автор
        if ($blog->getAuthor() !== $this->getUser()) {
            throw new AccessDeniedException('Вы не можете удалить чужой блог.');
        }

        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->request->get('_token'))) {
            $dm->remove($blog);
            $dm->flush();

            $this->addFlash('success', 'Блог успешно удален!');
        }

        return $this->redirectToRoute('blog_list');
    }
}
