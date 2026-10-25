<?php

namespace App\Service;

use App\Entity\Enum\OrderType;
use App\Entity\Enum\OrderBillingStatus;
use App\Entity\Enum\OrderOperationalStatus;
use App\Entity\Order;
use App\Entity\Cashier;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class OrderEditor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderLockService $lockService,
        private readonly OrderHistoryRecorder $historyRecorder,
        private readonly OrderItemFactory $orderItemFactory,
    ) {
    }

    public function update(Order $order, User $user, array $data): void
    {
        $this->lockService->assertHeldBy($order, $user);
        $previousOperationalStatus = $order->getOperationalStatus();
        $before = [
            'orderType' => $order->getOrderType()->value,
            'tableNumber' => $order->getTableNumber(),
            'customerName' => $order->getCustomerName(),
            'customerPhone' => $order->getCustomerPhone(),
            'deliveryAddress' => $order->getDeliveryAddress(),
            'assignedCashier' => $order->getAssignedCashier()?->getUsername(),
        ];
        $orderType = $this->parseType($data['orderType'] ?? $order->getOrderType()->value);

        $tableNumber = $this->nullableString($data['tableNumber'] ?? null);
        $customerName = $this->nullableString($data['customerName'] ?? null);
        $customerPhone = $this->nullableString($data['customerPhone'] ?? null);
        $deliveryAddress = $this->nullableString($data['deliveryAddress'] ?? null);
        $cashier = $this->findActiveCashier($data['assignedCashierId'] ?? $order->getAssignedCashier()?->getId());

        if ($orderType === OrderType::TABLE && $tableNumber === null) {
            throw new InvalidArgumentException('Debe indicar el número de mesa.');
        }

        if ($orderType === OrderType::DELIVERY) {
            foreach ([$customerName, $customerPhone, $deliveryAddress] as $value) {
                if ($value === null) {
                    throw new InvalidArgumentException('El domicilio requiere nombre, teléfono y dirección.');
                }
            }
        }

        $order
            ->setOrderType($orderType)
            ->setTableNumber($orderType === OrderType::TABLE ? $tableNumber : null)
            ->setCustomerName($customerName)
            ->setCustomerPhone($customerPhone)
            ->setDeliveryAddress($orderType === OrderType::DELIVERY ? $deliveryAddress : null)
            ->setAssignedCashier($cashier)
            ->setUpdatedBy($user);

        if ($previousOperationalStatus === OrderOperationalStatus::REJECTED
            && $order->getBillingStatus() === OrderBillingStatus::PENDING
        ) {
            $order->setOperationalStatus(OrderOperationalStatus::PREPARING);
        }

        if (array_key_exists('items', $data)) {
            $this->replaceItems($order, $data['items'], $user);
        }

        $this->historyRecorder->record($order, $user, 'order.updated', [
            'before' => $before,
            'after' => [
                'orderType' => $order->getOrderType()->value,
                'tableNumber' => $order->getTableNumber(),
                'customerName' => $order->getCustomerName(),
                'customerPhone' => $order->getCustomerPhone(),
                'deliveryAddress' => $order->getDeliveryAddress(),
                'assignedCashier' => $order->getAssignedCashier()?->getUsername(),
            ],
        ],
            $previousOperationalStatus->value,
            $order->getOperationalStatus()->value,
        );

        $this->entityManager->flush();
        $this->lockService->release($order, $user);
    }

    private function replaceItems(Order $order, mixed $items, User $user): void
    {
        if (!is_array($items) || $items === []) {
            throw new InvalidArgumentException('La orden debe conservar al menos un ítem.');
        }

        $oldItems = $order->getItems()->toArray();
        foreach ($oldItems as $oldItem) {
            $order->removeItem($oldItem);
            $this->entityManager->remove($oldItem);
        }

        foreach (array_values($items) as $position => $itemData) {
            if (!is_array($itemData)) {
                throw new InvalidArgumentException('El formato de los ítems no es válido.');
            }

            $productIds = $itemData['componentProductIds'] ?? [];
            if (!is_array($productIds) || $productIds === []) {
                throw new InvalidArgumentException('Cada combo debe tener al menos un componente.');
            }

            $products = array_map(function (mixed $productId): Product {
                $id = filter_var($productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $product = $id !== false ? $this->entityManager->getRepository(Product::class)->find($id) : null;
                if (!$product instanceof Product || !$product->isActive()) {
                    throw new InvalidArgumentException('Uno de los productos del combo no existe o está inactivo.');
                }
                return $product;
            }, $productIds);

            $newItem = $this->orderItemFactory->createFromProducts(
                $products,
                sprintf('Combo %d', $position + 1),
                $itemData['quantity'] ?? 1,
                $position,
            );
            $newItem->setCreatedBy($user)->setUpdatedBy($user);
            $order->addItem($newItem);
        }

        $this->historyRecorder->record($order, $user, 'order.items_replaced', [
            'previousItems' => count($oldItems),
            'newItems' => count($items),
        ]);
    }

    private function parseType(mixed $value): OrderType
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('El tipo de pedido no es válido.');
        }

        return OrderType::tryFrom($value)
            ?? throw new InvalidArgumentException('El tipo de pedido no es válido.');
    }

    private function findActiveCashier(mixed $id): User
    {
        $cashierUuid = is_string($id) ? trim($id) : '';
        $cashier = $cashierUuid !== '' ? $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->innerJoin('u.cashier', 'c')
            ->innerJoin('c.boxes', 'b')
            ->where('c.uuid = :uuid')
            ->andWhere('u.active = :userActive')
            ->andWhere('c.active = :cashierActive')
            ->setParameter('uuid', $cashierUuid)
            ->setParameter('userActive', true)
            ->setParameter('cashierActive', true)
            ->getQuery()
            ->getOneOrNullResult() : null;

        if (!$cashier instanceof User || !$cashier->isActive()) {
            throw new InvalidArgumentException('Debe seleccionar un cajero activo.');
        }

        return $cashier;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
