<?php

namespace App\Service;

use App\Entity\Enum\OrderType;
use App\Entity\Enum\PaymentMethod;
use App\Entity\CashBox;
use App\Entity\Order;
use App\Entity\OrderPaymentAllocation;
use App\Entity\User;
use InvalidArgumentException;

final class CashierPaymentService
{
    /** @return array{paymentMethod: PaymentMethod, deliveryFee: string, cashAmount: string, digitalAmount: string, cashReceived: ?string, changeDue: ?string, allocations: list<OrderPaymentAllocation>} */
    public function apply(Order $order, User $cashier, array $data): array
    {
        $deliveryFee = $this->money($data['deliveryFee'] ?? '0');
        if ($order->getOrderType() !== OrderType::DELIVERY && $deliveryFee !== '0.00') {
            throw new InvalidArgumentException('El valor del domicilio solo aplica a pedidos a domicilio.');
        }

        $total = $this->add($order->getTotal(), $deliveryFee);

        [$allocations, $method, $cashAmount, $digitalAmount] = $this->applyAllocations(
            $order,
            $cashier,
            $data['allocations'] ?? [],
            $total,
        );

        $cashReceived = null;
        $changeDue = null;
        if ($method === PaymentMethod::CASH || $method === PaymentMethod::MIXED) {
            $cashReceived = $this->money($data['cashReceived'] ?? '0');
            if ($this->compare($cashReceived, $cashAmount) < 0) {
                throw new InvalidArgumentException('El efectivo recibido no puede ser menor al valor en efectivo.');
            }
            $changeDue = $this->subtract($cashReceived, $cashAmount);
        }

        $order
            ->setPaymentMethod($method)
            ->setDeliveryFee($deliveryFee)
            ->setCashAmount($cashAmount)
            ->setDigitalAmount($digitalAmount)
            ->setCashReceived($cashReceived)
            ->setChangeDue($changeDue)
            ->setUpdatedBy($cashier);

        return compact('method', 'deliveryFee', 'cashAmount', 'digitalAmount', 'cashReceived', 'changeDue', 'allocations') + ['total' => $total];
    }

    /** Persists the payment distribution while the order is still being configured. */
    public function saveDraft(Order $order, User $cashier, array $data): array
    {
        $deliveryFee = $this->money($data['deliveryFee'] ?? '0');
        if ($order->getOrderType() !== OrderType::DELIVERY && $deliveryFee !== '0.00') {
            throw new InvalidArgumentException('El valor del domicilio solo aplica a pedidos a domicilio.');
        }

        $total = $this->add($order->getTotal(), $deliveryFee);
        [$allocations, $method, $cashAmount, $digitalAmount] = $this->applyAllocations(
            $order,
            $cashier,
            $data['allocations'] ?? [],
            $total,
        );

        if ($order->getCashReceived() !== null && $this->compare($order->getCashReceived(), $cashAmount) < 0) {
            $order->setCashReceived(null)->setChangeDue(null);
        }

        $order
            ->setPaymentMethod($method)
            ->setDeliveryFee($deliveryFee)
            ->setCashAmount($cashAmount)
            ->setDigitalAmount($digitalAmount)
            ->setUpdatedBy($cashier);

        return compact('method', 'deliveryFee', 'cashAmount', 'digitalAmount', 'allocations') + ['total' => $total];
    }

    /** @return list<OrderPaymentAllocation> */
    private function applyAllocations(Order $order, User $cashier, mixed $rawAllocations, string $total): array
    {
        $module = $cashier->getCashier();
        if ($module === null) {
            throw new InvalidArgumentException('El usuario no tiene un módulo de pago asignado.');
        }
        if (!is_array($rawAllocations) || $rawAllocations === []) {
            throw new InvalidArgumentException('Selecciona la caja o cajas destino del pago.');
        }

        foreach ($order->getPaymentAllocations()->toArray() as $previousAllocation) {
            $order->removePaymentAllocation($previousAllocation);
        }

        $allocations = [];
        $cashTotal = '0.00';
        $digitalTotal = '0.00';
        $methods = [];
        foreach ($rawAllocations as $rawAllocation) {
            if (!is_array($rawAllocation)) {
                throw new InvalidArgumentException('La distribución del pago no es válida.');
            }

            $amount = $this->money($rawAllocation['amount'] ?? '0');
            $boxUuid = trim((string) ($rawAllocation['cashBoxUuid'] ?? ''));
            $box = $this->findModuleBox($module->getBoxes()->toArray(), $boxUuid);
            if ($amount === '0.00' || $box === null) {
                throw new InvalidArgumentException('Cada distribución debe tener una caja válida y un monto mayor que cero.');
            }

            $allocationMethod = $box->getMoneyType() === \App\Entity\Enum\CashBoxMoneyType::CASH
                ? PaymentMethod::CASH
                : PaymentMethod::DIGITAL;
            $methods[$allocationMethod->value] = true;

            $allocation = (new OrderPaymentAllocation())
                ->setCashBox($box)
                ->setMethod($allocationMethod)
                ->setAmount($amount)
                ->setCreatedBy($cashier)
                ->setUpdatedBy($cashier);
            $order->addPaymentAllocation($allocation);
            $allocations[] = $allocation;

            if ($allocationMethod === PaymentMethod::CASH) {
                $cashTotal = $this->add($cashTotal, $amount);
            } else {
                $digitalTotal = $this->add($digitalTotal, $amount);
            }
        }

        if ($this->add($cashTotal, $digitalTotal) !== $total) {
            throw new InvalidArgumentException('La distribución entre cajas debe coincidir con el total a cobrar.');
        }

        $method = count($methods) > 1 ? PaymentMethod::MIXED : (isset($methods[PaymentMethod::CASH->value]) ? PaymentMethod::CASH : PaymentMethod::DIGITAL);

        return [$allocations, $method, $cashTotal, $digitalTotal];
    }

    /** @param list<CashBox> $boxes */
    private function findModuleBox(array $boxes, string $uuid): ?CashBox
    {
        foreach ($boxes as $box) {
            if ($box->getUuid() === $uuid) {
                return $box;
            }
        }

        return null;
    }

    private function money(mixed $value): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException('Los valores de pago no son válidos.');
        }
        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Los valores de pago deben ser positivos y tener máximo dos decimales.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return sprintf('%d.%02d', (int) $whole, (int) str_pad($fraction, 2, '0'));
    }

    private function add(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) + $this->toCents($right));
    }

    private function subtract(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) - $this->toCents($right));
    }

    private function compare(string $left, string $right): int
    {
        return $this->toCents($left) <=> $this->toCents($right);
    }

    private function toCents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function fromCents(int $value): string
    {
        return sprintf('%d.%02d', intdiv($value, 100), $value % 100);
    }
}
