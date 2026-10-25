<?php

namespace App\Service;

use App\Entity\Composite;
use App\Entity\CompositeComponent;
use App\Entity\OrderItem;
use App\Entity\OrderItemComponent;
use App\Entity\Product;
use InvalidArgumentException;

final class OrderItemFactory
{
    public function createFromProduct(Product $product, string|int $quantity = 1, int $position = 0): OrderItem
    {
        $this->assertActive($product->isActive(), 'El producto no está activo.');

        $normalizedQuantity = $this->normalizeQuantity($quantity);
        $item = (new OrderItem())
            ->setSourceType('product')
            ->setSourceId($product->getId())
            ->setName($product->getName())
            ->setQuantity($normalizedQuantity)
            ->setCostPrice($this->normalizeMoney($product->getCostPrice()))
            ->setSalePrice($this->normalizeMoney($product->getSalePrice()))
            ->setTotal($this->multiplyMoney($product->getSalePrice(), $normalizedQuantity))
            ->setPosition($position);

        $item->addComponent(
            (new OrderItemComponent())
                ->setSourceProductId($product->getId())
                ->setProductName($product->getName())
                ->setQuantity($product->getMeasure())
                ->setUnit($product->getUnit())
                ->setCostPrice($this->normalizeMoney($product->getCostPrice()))
                ->setSalePrice($this->normalizeMoney($product->getSalePrice()))
                ->setPosition(0),
        );

        return $item;
    }

    public function createFromComposite(Composite $composite, string|int $quantity = 1, int $position = 0): OrderItem
    {
        $this->assertActive($composite->isActive(), 'La plantilla compuesta no está activa.');

        if ($composite->getComponents()->isEmpty()) {
            throw new InvalidArgumentException('La plantilla compuesta debe tener al menos un componente.');
        }

        $normalizedQuantity = $this->normalizeQuantity($quantity);
        $item = (new OrderItem())
            ->setSourceType('composite')
            ->setSourceId($composite->getId())
            ->setName($composite->getName())
            ->setQuantity($normalizedQuantity)
            ->setCostPrice($this->normalizeMoney($composite->getCostPrice()))
            ->setSalePrice($this->normalizeMoney($composite->getSalePrice()))
            ->setTotal($this->multiplyMoney($composite->getSalePrice(), $normalizedQuantity))
            ->setPosition($position);

        foreach ($composite->getComponents() as $component) {
            $item->addComponent($this->copyComponent($component));
        }

        return $item;
    }

    /**
     * Creates an ad-hoc composite from active products selected in the sale view.
     * Only product identifiers arrive from the browser; all snapshot values come
     * from the current catalog.
     *
     * @param list<Product> $products
     */
    public function createFromProducts(array $products, string $name, string|int $quantity = 1, int $position = 0): OrderItem
    {
        if ($products === []) {
            throw new InvalidArgumentException('El compuesto debe tener al menos un producto.');
        }

        $normalizedQuantity = $this->normalizeQuantity($quantity);
        $item = (new OrderItem())
            ->setSourceType('custom_composite')
            ->setName($name !== '' ? $name : 'Compuesto personalizado')
            ->setQuantity($normalizedQuantity)
            ->setPosition($position);

        foreach (array_values($products) as $componentPosition => $product) {
            if (!$product instanceof Product) {
                throw new InvalidArgumentException('Cada componente debe ser un producto válido.');
            }

            $this->assertActive($product->isActive(), sprintf('El producto "%s" no está activo.', $product->getName()));
            $item->addComponent(
                (new OrderItemComponent())
                    ->setSourceProductId($product->getId())
                    ->setProductName($product->getName())
                    ->setQuantity($product->getMeasure())
                    ->setUnit($product->getUnit())
                    ->setCostPrice($this->normalizeMoney($product->getCostPrice()))
                    ->setSalePrice($this->normalizeMoney($product->getSalePrice()))
                    ->setPosition($componentPosition),
            );
        }

        $costCents = 0;
        $saleCents = 0;
        foreach ($item->getComponents() as $component) {
            $costCents += $this->toScaledInteger($component->getCostPrice(), 2);
            $saleCents += $this->toScaledInteger($component->getSalePrice(), 2);
        }

        $cost = $this->formatScaled($costCents, 2);
        $sale = $this->formatScaled($saleCents, 2);
        return $item
            ->setCostPrice($cost)
            ->setSalePrice($sale)
            ->setTotal($this->multiplyMoney($sale, $normalizedQuantity));
    }

    private function copyComponent(CompositeComponent $component): OrderItemComponent
    {
        $product = $component->getProduct();

        if (!$product instanceof Product) {
            throw new InvalidArgumentException('Cada componente debe tener un producto válido.');
        }

        $this->assertActive($product->isActive(), sprintf('El producto "%s" no está activo.', $product->getName()));

        return (new OrderItemComponent())
            ->setSourceProductId($product->getId())
            ->setProductName($product->getName())
            ->setQuantity($component->getQuantity())
            ->setUnit($component->getUnit())
            ->setCostPrice($this->normalizeMoney($product->getCostPrice()))
            ->setSalePrice($this->normalizeMoney($product->getSalePrice()))
            ->setPosition($component->getPosition());
    }

    private function assertActive(bool $active, string $message): void
    {
        if (!$active) {
            throw new InvalidArgumentException($message);
        }
    }

    private function normalizeMoney(string $value): string
    {
        return $this->formatScaled($this->toScaledInteger($value, 2), 2);
    }

    private function normalizeQuantity(string|int $value): int
    {
        $value = (string) $value;
        if (!preg_match('/^\d+$/', trim($value))) {
            throw new InvalidArgumentException(sprintf('La cantidad "%s" debe ser un número entero.', $value));
        }

        $quantity = (int) $value;

        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }

        return $quantity;
    }

    private function multiplyMoney(string $money, int $quantity): string
    {
        $moneyInCents = $this->toScaledInteger($money, 2);
        $totalInCents = $moneyInCents * $quantity;

        return $this->formatScaled($totalInCents, 2);
    }

    private function toScaledInteger(string $value, int $scale): int
    {
        $value = trim($value);

        if (!preg_match('/^\d+(?:\.(\d+))?$/', $value, $matches)) {
            throw new InvalidArgumentException(sprintf('Valor decimal inválido: "%s".', $value));
        }

        $fraction = $matches[1] ?? '';

        if (strlen($fraction) > $scale) {
            throw new InvalidArgumentException(sprintf('El valor "%s" supera %d decimales.', $value, $scale));
        }

        [$wholePart, $fractionPart] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $wholePart) * (10 ** $scale)
            + (int) str_pad($fractionPart, $scale, '0');
    }

    private function formatScaled(int $value, int $scale): string
    {
        $base = 10 ** $scale;
        $whole = intdiv($value, $base);
        $fraction = str_pad((string) ($value % $base), $scale, '0', STR_PAD_LEFT);

        return sprintf('%d.%s', $whole, $fraction);
    }
}
