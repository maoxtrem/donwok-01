<?php

namespace App\Controller;

use App\Service\OrderCreator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('/api/orders', name: 'api_orders_create', methods: ['POST'])]
    public function create(Request $request, OrderCreator $orderCreator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'El cuerpo de la solicitud debe ser JSON válido.'], 400);
        }

        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'La sesión no tiene un usuario válido.'], 401);
        }

        try {
            $order = $orderCreator->createSubmitted($user, $data);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json([
            'id' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'orderDate' => $order->getOrderDate()->format('Y-m-d'),
            'operationalStatus' => $order->getOperationalStatus()->value,
            'billingStatus' => $order->getBillingStatus()->value,
        ], 201);
    }
}
