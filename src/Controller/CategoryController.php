<?php

namespace App\Controller;

use App\Document\Category;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'category_list', methods: ['GET'])]
    public function index(DocumentManager $dm): Response
    {
        // Получаем все категории и сортируем по имени в алфавитном порядке
        $categories = $dm->getRepository(Category::class)->findBy([], ['name' => 'ASC']);

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/create', name: 'category_create', methods: ['POST'])]
    public function create(Request $request, DocumentManager $dm): Response
    {
        $name = $request->request->get('name');

        if (!empty($name)) {
            $category = new Category();
            $category->setName($name);

            $dm->persist($category);
            $dm->flush();

            $this->addFlash('success', 'Раздел успешно создан!');
        }

        return $this->redirectToRoute('category_list');
    }
}