<?php

namespace App\Controller;

use App\Entity\Enum\OrderOperationalStatus;
use App\Entity\Order;
use App\Security\PermissionChecker;
use App\Service\OrderHistoryRecorder;
use App\Service\OrderRealtimePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrdersController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/ordenes', name: 'app_orders', methods: ['GET'])]
    public function page(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('orders.read')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('orders/index.html.twig', [
            'orders' => $this->findActiveOrders(),
            'canDeliver' => $permissions->isGranted('orders.deliver_table'),
        ]);
    }

    #[Route('/api/orders/{id}/deliver', name: 'api_orders_deliver', methods: ['POST'])]
    public function deliver(
        int $id,
        PermissionChecker $permissions,
        OrderHistoryRecorder $historyRecorder,
        OrderRealtimePublisher $realtimePublisher,
    ): JsonResponse {
        if (!$permissions->isGranted('orders.deliver_table')) {
            return $this->json(['error' => 'No tienes permiso para marcar órdenes como entregadas.'], Response::HTTP_FORBIDDEN);
        }

        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if (!$order instanceof Order) {
            return $this->json(['error' => 'La orden no existe.'], Response::HTTP_NOT_FOUND);
        }

        if (!in_array($order->getOperationalStatus(), [OrderOperationalStatus::SERVED, OrderOperationalStatus::PACKED], true)) {
            return $this->json(['error' => 'La orden todavía no está lista para entregar.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $previousStatus = $order->getOperationalStatus()->value;
        $user = $this->currentUser();
        $order->setOperationalStatus(OrderOperationalStatus::DELIVERED)->setUpdatedBy($user);
        $historyRecorder->record(
            $order,
            $user,
            'order.delivered',
            [],
            $previousStatus,
            OrderOperationalStatus::DELIVERED->value,
        );
        $this->entityManager->flush();
        $realtimePublisher->orderChanged($order, 'order.delivered');

        return $this->json([
            'id' => $order->getId(),
            'status' => OrderOperationalStatus::DELIVERED->value,
        ]);
    }

    #[Route('/api/orders', name: 'api_orders_list', methods: ['GET'])]
    public function list(PermissionChecker $permissions): JsonResponse
    {
        if (!$permissions->isGranted('orders.read')) {
            return $this->json(['error' => 'No tienes permiso para consultar órdenes.'], Response::HTTP_FORBIDDEN);
        }

        $orders = array_map(static function (Order $order): array {
            return [
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'orderDate' => $order->getOrderDate()->format('Y-m-d'),
                'orderType' => $order->getOrderType()->value,
                'operationalStatus' => $order->getOperationalStatus()->value,
                'billingStatus' => $order->getBillingStatus()->value,
                'total' => $order->getTotal(),
                'assignedCashier' => $order->getAssignedCashier()?->getUsername(),
                'createdBy' => $order->getCreatedBy()?->getUsername(),
                'items' => array_map(static fn ($item): array => [
                    'name' => $item->getName(),
                    'quantity' => $item->getQuantity(),
                    'components' => array_map(static fn ($component): array => [
                        'name' => $component->getProductName(),
                        'quantity' => $component->getQuantity(),
                        'unit' => $component->getUnit(),
                    ], $item->getComponents()->toArray()),
                ], $order->getItems()->toArray()),
            ];
        }, $this->findActiveOrders());

        return $this->json(['orders' => $orders]);
    }

    /** @return list<Order> */
    private function findActiveOrders(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.operationalStatus != :delivered')
            ->setParameter('delivered', OrderOperationalStatus::DELIVERED->value)
            ->orderBy('o.orderDate', 'DESC')
            ->addOrderBy('o.orderNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function currentUser(): \App\Entity\User
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
