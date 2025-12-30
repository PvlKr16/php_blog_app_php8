<?php

namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'notifications_list', methods: ['GET'])]
    public function list(NotificationService $notificationService): Response
    {
        $unreadBlogs = $notificationService->getUnreadBlogs($this->getUser());

        return $this->render('notifications/list.html.twig', [
            'unreadBlogs' => $unreadBlogs,
        ]);
    }
}