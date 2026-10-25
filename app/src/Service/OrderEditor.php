<?php

namespace App\Service;

use App\Entity\Enum\OrderType;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class OrderEditor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderLockService $lockService,
    ) {
    }

    public function update(Order $order, User $user, array $data): void
    {
        $this->lockService->assertHeldBy($order, $user);
        $orderType = $this->parseType($data['orderType'] ?? $order->getOrderType()->value);

        $tableNumber = $this->nullableString($data['tableNumber'] ?? null);
        $customerName = $this->nullableString($data['customerName'] ?? null);
        $customerPhone = $this->nullableString($data['customerPhone'] ?? null);
        $deliveryAddress = $this->nullableString($data['deliveryAddress'] ?? null);

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
            ->setUpdatedBy($user);

        $this->entityManager->flush();
        $this->lockService->release($order, $user);
    }

    private function parseType(mixed $value): OrderType
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('El tipo de pedido no es válido.');
        }

        return OrderType::tryFrom($value)
            ?? throw new InvalidArgumentException('El tipo de pedido no es válido.');
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
