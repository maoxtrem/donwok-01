<?php

namespace App\Service;

use App\Entity\Enum\OrderBillingStatus;
use App\Entity\CashBox;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class InvoiceCreator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(Order $order, User $cashier): Invoice
    {
        if ($order->isInvoiced()) {
            throw new InvalidArgumentException('La orden ya está facturada.');
        }
        if ($order->getPaymentMethod() === null) {
            throw new InvalidArgumentException('Primero debes validar el método de pago.');
        }
        if ($order->getPaymentAllocations()->isEmpty()) {
            throw new InvalidArgumentException('Primero debes indicar las cajas destino del pago.');
        }
        if (($order->getDigitalAmount() ?? '0.00') !== '0.00' && !$order->getPaymentReceiptKey()) {
            throw new InvalidArgumentException('Debes cargar el comprobante porque el pago digital es mayor que cero.');
        }

        $invoice = (new Invoice())
            ->setInvoiceNumber($this->nextNumber())
            ->setOrder($order)
            ->setPaymentMethod($order->getPaymentMethod())
            ->setSubtotal($order->getTotal())
            ->setDeliveryFee($order->getDeliveryFee())
            ->setTotal($order->getPayableTotal())
            ->setCashAmount($order->getCashAmount() ?? '0.00')
            ->setDigitalAmount($order->getDigitalAmount() ?? '0.00')
            ->setCashReceived($order->getCashReceived())
            ->setChangeDue($order->getChangeDue())
            ->setItemsSnapshot($this->snapshotItems($order))
            ->setCreatedBy($cashier)
            ->setUpdatedBy($cashier);

        $order
            ->setBillingStatus(OrderBillingStatus::INVOICED)
            ->setUpdatedBy($cashier);

        foreach ($order->getPaymentAllocations() as $allocation) {
            $box = $allocation->getCashBox();
            if ($box instanceof CashBox) {
                $box->setBalance($this->addMoney($box->getBalance(), $allocation->getAmount()));
            }
        }

        $this->entityManager->persist($invoice);

        return $invoice;
    }

    private function nextNumber(): int
    {
        $last = $this->entityManager->createQueryBuilder()
            ->select('MAX(i.invoiceNumber)')
            ->from(Invoice::class, 'i')
            ->getQuery()
            ->getSingleScalarResult();

        return $last === null ? 0 : ((int) $last + 1);
    }

    private function snapshotItems(Order $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            $components = [];
            foreach ($item->getComponents() as $component) {
                $components[] = [
                    'name' => $component->getProductName(),
                    'quantity' => $component->getQuantity(),
                    'unit' => $component->getUnit(),
                    'salePrice' => $component->getSalePrice(),
                ];
            }
            $items[] = [
                'name' => $item->getName(),
                'quantity' => $item->getQuantity(),
                'salePrice' => $item->getSalePrice(),
                'total' => $item->getTotal(),
                'components' => $components,
            ];
        }

        return $items;
    }

    private function addMoney(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) + $this->toCents($right));
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
