<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Если пользователь авторизован - перенаправляем на блоги
        if ($this->getUser()) {
            return $this->redirectToRoute('blog_list');
        }

        // Если не авторизован - показываем страницу выбора
        return $this->render('home/index.html.twig');
    }
}