<?php

namespace App\Controller;

use App\Entity\Enum\OrderOperationalStatus;
use App\Entity\Enum\OrderType;
use App\Entity\Order;
use App\Security\PermissionChecker;
use App\Service\OrderHistoryRecorder;
use App\Service\OrderRealtimePublisher;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class KitchenController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/cocina', name: 'app_kitchen', methods: ['GET'])]
    public function page(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('kitchen.read')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('kitchen/index.html.twig', ['orders' => $this->prepareOrders($this->findActiveOrders())]);
    }

    #[Route('/cocina/tv', name: 'app_kitchen_tv', methods: ['GET'])]
    public function tv(PermissionChecker $permissions): Response
    {
        if (!$permissions->isGranted('kitchen.read')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('kitchen/tv.html.twig', [
            'orders' => $this->prepareOrders($this->findPreparingOrders()),
        ]);
    }

    #[Route('/api/kitchen/orders/{id}/status', name: 'api_kitchen_order_status', methods: ['PATCH'])]
    public function updateStatus(
        int $id,
        Request $request,
        PermissionChecker $permissions,
        OrderHistoryRecorder $historyRecorder,
        OrderRealtimePublisher $realtimePublisher,
    ): JsonResponse {
        if (!$permissions->isGranted('kitchen.update_status')) {
            return $this->json(['error' => 'No tienes permiso para cambiar estados en cocina.'], Response::HTTP_FORBIDDEN);
        }

        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if (!$order instanceof Order) {
            return $this->json(['error' => 'La orden no existe.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $status = is_array($data) && is_string($data['status'] ?? null) ? $data['status'] : null;
        $reason = is_array($data) && is_string($data['reason'] ?? null) ? trim($data['reason']) : null;

        try {
            $newStatus = $this->parseStatus($status);
            $this->assertTransition($order, $newStatus);

            if ($newStatus === OrderOperationalStatus::REJECTED) {
                if (!$permissions->isGranted('kitchen.reject')) {
                    throw new InvalidArgumentException('No tienes permiso para rechazar comandas.');
                }
                if ($reason === null || $reason === '') {
                    throw new InvalidArgumentException('Debes indicar el motivo del rechazo.');
                }
            }
        } catch (InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $previousStatus = $order->getOperationalStatus()->value;
        $order->setOperationalStatus($newStatus)->setUpdatedBy($this->currentUser());
        $historyRecorder->record(
            $order,
            $this->currentUser(),
            'kitchen.status_changed',
            array_filter(['reason' => $reason]),
            $previousStatus,
            $newStatus->value,
        );
        $this->entityManager->flush();
        $realtimePublisher->orderChanged($order, 'kitchen.status_changed');

        return $this->json([
            'id' => $order->getId(),
            'status' => $newStatus->value,
        ]);
    }

    /** @return list<Order> */
    private function findActiveOrders(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.operationalStatus != :delivered')
            ->setParameter('delivered', OrderOperationalStatus::DELIVERED->value)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Order> */
    private function findPreparingOrders(): array
    {
        return $this->entityManager->getRepository(Order::class)->findBy(
            ['operationalStatus' => OrderOperationalStatus::PREPARING->value],
            ['createdAt' => 'ASC'],
        );
    }

    private function prepareOrders(array $orders): array
    {
        return array_map(function (Order $order): array {
            $items = array_map(function ($item): array {
                $name = $item->getName();
                if ($item->getSourceType() !== 'product' && preg_match('/^combo\s+\d+$/i', trim($name))) {
                    $name = 'Combo';
                }

                $components = [];
                foreach ($item->getComponents() as $component) {
                    $key = sprintf('%s|%s|%d', $component->getProductName(), $component->getUnit(), $component->getQuantity());
                    $components[$key] ??= [
                        'productName' => $component->getProductName(),
                        'quantity' => 0,
                        'unit' => $component->getUnit(),
                    ];
                    $components[$key]['quantity'] += $component->getQuantity();
                }

                return [
                    'name' => $name,
                    'quantity' => $item->getQuantity(),
                    'components' => array_values($components),
                ];
            }, $order->getItems()->toArray());

            return ['order' => $order, 'items' => $items];
        }, $orders);
    }

    private function parseStatus(?string $status): OrderOperationalStatus
    {
        return OrderOperationalStatus::tryFrom($status ?? '')
            ?? throw new InvalidArgumentException('El estado de cocina no es válido.');
    }

    private function assertTransition(Order $order, OrderOperationalStatus $newStatus): void
    {
        $current = $order->getOperationalStatus();
        $allowed = match ($current) {
            OrderOperationalStatus::PREPARING => [OrderOperationalStatus::PREPARED, OrderOperationalStatus::REJECTED],
            OrderOperationalStatus::PREPARED => $order->getOrderType() === OrderType::TABLE
                ? [OrderOperationalStatus::SERVED, OrderOperationalStatus::REJECTED]
                : [OrderOperationalStatus::PACKED, OrderOperationalStatus::REJECTED],
            default => [],
        };

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException('La transición de estado no está permitida.');
        }
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
