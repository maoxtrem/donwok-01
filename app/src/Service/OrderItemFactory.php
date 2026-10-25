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
    public function createFromProduct(Product $product, string $quantity = '1.000', int $position = 0): OrderItem
    {
        $this->assertActive($product->isActive(), 'El producto no está activo.');

        $normalizedQuantity = $this->normalizeQuantity($quantity);
        $item = (new OrderItem())
            ->setSourceType('product')
            ->setSourceId($product->getId())
            ->setName($product->getName())
            ->setQuantity($normalizedQuantity)
            ->setUnitPrice($this->normalizeMoney($product->getPrice()))
            ->setTotal($this->multiplyMoney($product->getPrice(), $normalizedQuantity))
            ->setPosition($position);

        $item->addComponent(
            (new OrderItemComponent())
                ->setSourceProductId($product->getId())
                ->setProductName($product->getName())
                ->setQuantity('1.000')
                ->setUnit($product->getUnit())
                ->setUnitPrice($this->normalizeMoney($product->getPrice()))
                ->setPosition(0),
        );

        return $item;
    }

    public function createFromComposite(Composite $composite, string $quantity = '1.000', int $position = 0): OrderItem
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
            ->setUnitPrice($this->normalizeMoney($composite->getPrice()))
            ->setTotal($this->multiplyMoney($composite->getPrice(), $normalizedQuantity))
            ->setPosition($position);

        foreach ($composite->getComponents() as $component) {
            $item->addComponent($this->copyComponent($component));
        }

        return $item;
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
            ->setQuantity($this->normalizeQuantity($component->getQuantity()))
            ->setUnit($component->getUnit())
            ->setUnitPrice($this->normalizeMoney($product->getPrice()))
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

    private function normalizeQuantity(string $value): string
    {
        $scaled = $this->toScaledInteger($value, 3);

        if ($scaled <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }

        return $this->formatScaled($scaled, 3);
    }

    private function multiplyMoney(string $money, string $quantity): string
    {
        $moneyInCents = $this->toScaledInteger($money, 2);
        $quantityInThousandths = $this->toScaledInteger($quantity, 3);
        $totalInCents = intdiv(($moneyInCents * $quantityInThousandths) + 500, 1000);

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
