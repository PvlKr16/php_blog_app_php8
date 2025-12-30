<?php

namespace App\Controller\Api;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationApiController extends AbstractController
{
    #[Route('/count', name: 'api_notifications_count', methods: ['GET'])]
    public function getCount(NotificationService $notificationService): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['count' => 0]);
        }

        $count = $notificationService->getUnreadCount($this->getUser());

        return $this->json(['count' => $count]);
    }
}