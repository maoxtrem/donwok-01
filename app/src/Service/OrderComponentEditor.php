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
        private readonly OrderHistoryRecorder $historyRecorder,
    ) {
    }

    public function add(Order $order, OrderItem $item, User $user, int $productId): OrderItemComponent
    {
        $this->assertItemBelongsToOrder($order, $item);
        $this->lockService->assertHeldBy($order, $user);
        $product = $this->findActiveProduct($productId);
        $component = (new OrderItemComponent())
            ->setSourceProductId($product->getId())
            ->setProductName($product->getName())
            ->setQuantity($product->getMeasure())
            ->setUnit($product->getUnit())
            ->setCostPrice($product->getCostPrice())
            ->setSalePrice($product->getSalePrice())
            ->setPosition($item->getComponents()->count());

        $item->addComponent($component);
        $this->refreshItemPrices($item);
        $this->historyRecorder->record($order, $user, 'order_component.added', [
            'itemId' => $item->getId(), 'productId' => $product->getId(), 'productName' => $product->getName(),
            'measure' => $product->getMeasure(), 'unit' => $product->getUnit(), 'salePrice' => $product->getSalePrice(),
        ]);
        $item->setUpdatedBy($user);
        $this->entityManager->flush();

        return $component;
    }

    public function replace(Order $order, OrderItem $item, OrderItemComponent $component, User $user, int $productId): void
    {
        $this->assertItemBelongsToOrder($order, $item);
        $this->assertComponentBelongsToItem($item, $component);
        $this->lockService->assertHeldBy($order, $user);
        $product = $this->findActiveProduct($productId);
        $before = ['productId' => $component->getSourceProductId(), 'productName' => $component->getProductName(), 'measure' => $component->getQuantity(), 'unit' => $component->getUnit(), 'salePrice' => $component->getSalePrice()];

        $component
            ->setSourceProductId($product->getId())
            ->setProductName($product->getName())
            ->setQuantity($product->getMeasure())
            ->setUnit($product->getUnit())
            ->setCostPrice($product->getCostPrice())
            ->setSalePrice($product->getSalePrice());
        $this->refreshItemPrices($item);
        $this->historyRecorder->record($order, $user, 'order_component.replaced', [
            'itemId' => $item->getId(), 'before' => $before,
            'after' => ['productId' => $product->getId(), 'productName' => $product->getName(), 'measure' => $product->getMeasure(), 'unit' => $product->getUnit(), 'salePrice' => $product->getSalePrice()],
        ]);
        $item->setUpdatedBy($user);
        $this->entityManager->flush();
    }

    public function remove(Order $order, OrderItem $item, OrderItemComponent $component, User $user): void
    {
        $this->assertItemBelongsToOrder($order, $item);
        $this->assertComponentBelongsToItem($item, $component);
        $this->lockService->assertHeldBy($order, $user);
        $before = ['productId' => $component->getSourceProductId(), 'productName' => $component->getProductName(), 'measure' => $component->getQuantity(), 'unit' => $component->getUnit(), 'salePrice' => $component->getSalePrice()];

        if ($item->getComponents()->count() <= 1) {
            throw new InvalidArgumentException('El ítem debe conservar al menos un componente.');
        }

        $item->removeComponent($component);
        $this->refreshItemPrices($item);
        $this->historyRecorder->record($order, $user, 'order_component.removed', ['itemId' => $item->getId(), 'component' => $before]);
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

    private function refreshItemPrices(OrderItem $item): void
    {
        $costCents = 0;
        $saleCents = 0;

        foreach ($item->getComponents() as $component) {
            $costCents += $this->toCents($component->getCostPrice());
            $saleCents += $this->toCents($component->getSalePrice());
        }

        $item->setCostPrice($this->fromCents($costCents));
        $item->setSalePrice($this->fromCents($saleCents));
        $item->setTotal($this->fromCents($saleCents * $item->getQuantity()));
    }

    private function toCents(string $amount): int
    {
        [$whole, $decimal] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private function fromCents(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
