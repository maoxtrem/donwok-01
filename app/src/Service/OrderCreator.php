<?php

namespace App\Service;

use App\Entity\Composite;
use App\Entity\Enum\OrderOperationalStatus;
use App\Entity\Enum\OrderType;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class OrderCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderItemFactory $orderItemFactory,
    ) {
    }

    public function createSubmitted(User $creator, array $data): Order
    {
        $orderType = $this->parseOrderType($data['orderType'] ?? null);
        $this->validateDestination($orderType, $data);

        $cashier = $this->findActiveCashier($data['assignedCashierId'] ?? null);
        $items = $data['items'] ?? [];

        if (!is_array($items) || $items === []) {
            throw new InvalidArgumentException('La orden debe tener al menos un ítem.');
        }

        $today = new DateTimeImmutable('today');
        $order = (new Order())
            ->setOrderDate($today)
            ->setOrderNumber($this->nextOrderNumber($today))
            ->setOrderType($orderType)
            ->setOperationalStatus(OrderOperationalStatus::PREPARING)
            ->setTableNumber($this->nullableString($data['tableNumber'] ?? null))
            ->setCustomerName($this->nullableString($data['customerName'] ?? null))
            ->setCustomerPhone($this->nullableString($data['customerPhone'] ?? null))
            ->setDeliveryAddress($this->nullableString($data['deliveryAddress'] ?? null))
            ->setAssignedCashier($cashier)
            ->setCreatedBy($creator)
            ->setUpdatedBy($creator);

        foreach (array_values($items) as $position => $itemData) {
            if (!is_array($itemData)) {
                throw new InvalidArgumentException('El formato de los ítems no es válido.');
            }

            $item = $this->createItem($itemData, $position);
            $order->addItem($item);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function createItem(array $data, int $position): \App\Entity\OrderItem
    {
        $type = $data['type'] ?? null;
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = (string) ($data['quantity'] ?? '1.000');

        if (!is_string($type) || $id === false) {
            throw new InvalidArgumentException('Cada ítem debe indicar un tipo y un identificador válido.');
        }

        return match ($type) {
            'product' => $this->orderItemFactory->createFromProduct($this->findProduct($id), $quantity, $position),
            'composite' => $this->orderItemFactory->createFromComposite($this->findComposite($id), $quantity, $position),
            default => throw new InvalidArgumentException('El tipo de ítem no es válido.'),
        };
    }

    private function findProduct(int $id): Product
    {
        $product = $this->entityManager->getRepository(Product::class)->find($id);

        if (!$product instanceof Product || !$product->isActive()) {
            throw new InvalidArgumentException('El producto no existe o no está activo.');
        }

        return $product;
    }

    private function findComposite(int $id): Composite
    {
        $composite = $this->entityManager->getRepository(Composite::class)->find($id);

        if (!$composite instanceof Composite || !$composite->isActive()) {
            throw new InvalidArgumentException('El compuesto no existe o no está activo.');
        }

        return $composite;
    }

    private function findActiveCashier(mixed $id): User
    {
        $cashierId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $cashier = $cashierId !== false ? $this->entityManager->getRepository(User::class)->find($cashierId) : null;

        if (!$cashier instanceof User || !$cashier->isActive()) {
            throw new InvalidArgumentException('Debe seleccionar un cajero activo.');
        }

        return $cashier;
    }

    private function parseOrderType(mixed $value): OrderType
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Debe seleccionar el tipo de pedido.');
        }

        return OrderType::tryFrom($value)
            ?? throw new InvalidArgumentException('El tipo de pedido no es válido.');
    }

    private function validateDestination(OrderType $orderType, array $data): void
    {
        if ($orderType === OrderType::TABLE && $this->nullableString($data['tableNumber'] ?? null) === null) {
            throw new InvalidArgumentException('Debe indicar el número de mesa.');
        }

        if ($orderType === OrderType::DELIVERY) {
            foreach (['customerName' => 'nombre del cliente', 'customerPhone' => 'teléfono', 'deliveryAddress' => 'dirección'] as $field => $label) {
                if ($this->nullableString($data[$field] ?? null) === null) {
                    throw new InvalidArgumentException(sprintf('Debe indicar el %s del domicilio.', $label));
                }
            }
        }
    }

    private function nextOrderNumber(DateTimeImmutable $date): int
    {
        $lastOrder = $this->entityManager->getRepository(Order::class)->findOneBy(
            ['orderDate' => $date],
            ['orderNumber' => 'DESC'],
        );

        return ($lastOrder?->getOrderNumber() ?? 0) + 1;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
