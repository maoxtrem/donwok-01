<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderItemComponent;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class OrderComponentEditor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderLockService $lockService,
    ) {
    }

    public function add(Order $order, OrderItem $item, User $user, int $productId, string $quantity): OrderItemComponent
    {
        $this->assertItemBelongsToOrder($order, $item);
        $this->lockService->assertHeldBy($order, $user);
        $product = $this->findActiveProduct($productId);
        $component = (new OrderItemComponent())
            ->setSourceProductId($product->getId())
            ->setProductName($product->getName())
            ->setQuantity($this->normalizeQuantity($quantity))
            ->setUnit($product->getUnit())
            ->setUnitPrice($product->getPrice())
            ->setPosition($item->getComponents()->count());

        $item->addComponent($component);
        $item->setUpdatedBy($user);
        $this->entityManager->flush();

        return $component;
    }

    public function replace(Order $order, OrderItem $item, OrderItemComponent $component, User $user, int $productId, string $quantity): void
    {
        $this->assertItemBelongsToOrder($order, $item);
        $this->assertComponentBelongsToItem($item, $component);
        $this->lockService->assertHeldBy($order, $user);
        $product = $this->findActiveProduct($productId);

        $component
            ->setSourceProductId($product->getId())
            ->setProductName($product->getName())
            ->setQuantity($this->normalizeQuantity($quantity))
            ->setUnit($product->getUnit())
            ->setUnitPrice($product->getPrice());
        $item->setUpdatedBy($user);
        $this->entityManager->flush();
    }

    public function remove(Order $order, OrderItem $item, OrderItemComponent $component, User $user): void
    {
        $this->assertItemBelongsToOrder($order, $item);
        $this->assertComponentBelongsToItem($item, $component);
        $this->lockService->assertHeldBy($order, $user);

        if ($item->getComponents()->count() <= 1) {
            throw new InvalidArgumentException('El ítem debe conservar al menos un componente.');
        }

        $item->removeComponent($component);
        $item->setUpdatedBy($user);
        $this->entityManager->flush();
    }

    private function findActiveProduct(int $productId): Product
    {
        $product = $this->entityManager->getRepository(Product::class)->find($productId);

        if (!$product instanceof Product || !$product->isActive()) {
            throw new InvalidArgumentException('El producto no existe o no está activo.');
        }

        return $product;
    }

    private function assertItemBelongsToOrder(Order $order, OrderItem $item): void
    {
        if ($item->getOrder()?->getId() !== $order->getId()) {
            throw new InvalidArgumentException('El ítem no pertenece a la orden.');
        }
    }

    private function assertComponentBelongsToItem(OrderItem $item, OrderItemComponent $component): void
    {
        if ($component->getOrderItem()?->getId() !== $item->getId()) {
            throw new InvalidArgumentException('El componente no pertenece al ítem.');
        }
    }

    private function normalizeQuantity(string $quantity): string
    {
        $quantity = trim($quantity);

        if (!preg_match('/^\d+(?:\.(\d+))?$/', $quantity, $matches)) {
            throw new InvalidArgumentException('La cantidad no es válida.');
        }

        $fraction = $matches[1] ?? '';

        if (strlen($fraction) > 3 || (float) $quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero y tener máximo tres decimales.');
        }

        [$whole, $decimal] = array_pad(explode('.', $quantity, 2), 2, '');

        return sprintf('%d.%s', (int) $whole, str_pad($decimal, 3, '0'));
    }
}
