<?php

namespace App\Controller;

use App\Document\User;
use App\Document\Department;
use App\Form\RegistrationFormType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'register')]
    #[IsGranted('ROLE_ADMIN')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        DocumentManager $dm,
        FileUploader $fileUploader
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Проверяем что подразделение выбрано
            $departmentId = $form->get('departmentId')->getData();

            if (!$departmentId || $departmentId === '__new__') {
                $this->addFlash('error', 'Пожалуйста, выберите подразделение');
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Находим подразделение
            $department = $dm->getRepository(Department::class)->find($departmentId);

            if (!$department) {
                $this->addFlash('error', 'Подразделение не найдено');
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Устанавливаем подразделение
            $user->setDepartment($department);

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
                    $this->addFlash('warning', 'Не удалось загрузить аватар, но пользователь создан успешно.');
                }
            }

            $dm->persist($user);
            $dm->flush();

            $this->addFlash('success', 'Пользователь создан успешно!');

            return $this->redirectToRoute('blog_list');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/api/departments', name: 'api_departments', methods: ['GET'])]
    public function getDepartments(DocumentManager $dm): JsonResponse
    {
        $departments = $dm->getRepository(Department::class)
            ->findBy([], ['name' => 'ASC']);

        $data = array_map(function (Department $dept) {
            return [
                'id' => $dept->getId(),      // ID MongoDB
                'text' => $dept->getName(),  // Название для отображения
            ];
        }, $departments);

        return $this->json($data);
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

    #[Route('/api/departments/create', name: 'api_departments_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createDepartment(Request $request, DocumentManager $dm): JsonResponse
    {
        $name = trim($request->request->get('name', ''));

        if (empty($name)) {
            return $this->json([
                'success' => false,
                'error' => 'Название не может быть пустым'
            ], 400);
        }

        // Проверяем уникальность
        $existing = $dm->getRepository(Department::class)->findOneBy(['name' => $name]);
        if ($existing) {
            return $this->json([
                'success' => false,
                'error' => 'Такое подразделение уже существует'
            ], 400);
        }

        // Создаём новое подразделение
        $department = new Department();
        $department->setName($name);
        $dm->persist($department);
        $dm->flush();

        return $this->json([
            'success' => true,
            'department' => [
                'id' => $department->getId(),
                'name' => $department->getName(),
            ]
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}