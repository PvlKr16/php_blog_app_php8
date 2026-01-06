<?php

namespace App\Controller;

use App\Document\Department;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/departments')]
#[IsGranted('ROLE_ADMIN')]
class DepartmentController extends AbstractController
{
    #[Route('/', name: 'department_list', methods: ['GET'])]
    public function index(DocumentManager $dm): Response
    {
        // Получаем все подразделения и сортируем по имени в алфавитном порядке
        $departments = $dm->getRepository(Department::class)->findBy([], ['name' => 'ASC']);

        return $this->render('department/index.html.twig', [
            'departments' => $departments,
        ]);
    }

    #[Route('/create', name: 'department_create', methods: ['POST'])]
    public function create(Request $request, DocumentManager $dm): Response
    {
        $name = $request->request->get('name');

        if (!empty($name)) {
            $department = new Department();
            $department->setName($name);

            $dm->persist($department);
            $dm->flush();

            $this->addFlash('success', 'Подразделение успешно создано!');
        }

        return $this->redirectToRoute('department_list');
    }
}