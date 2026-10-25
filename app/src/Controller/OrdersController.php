<?php

namespace App\Controller;

use App\Entity\Enum\OrderOperationalStatus;
use App\Entity\Order;
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
    public function page(): Response
    {
        return $this->render('orders/index.html.twig', [
            'orders' => $this->findActiveOrders(),
        ]);
    }

    #[Route('/api/orders', name: 'api_orders_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
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
}
