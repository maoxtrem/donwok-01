<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderHistoryController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/ordenes/{id}/historial', name: 'app_order_history', methods: ['GET'])]
    public function index(int $id): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if (!$order instanceof Order) {
            throw $this->createNotFoundException('La orden no existe.');
        }

        return $this->render('orders/history.html.twig', [
            'order' => $order,
            'history' => $order->getHistory(),
            'user' => $this->getUser(),
        ]);
    }
}
