<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Invoice;
use App\Printing\PrintTicket;

final class OrderTicketFactory
{
    public function kitchen(Order $order): PrintTicket
    {
        $lines = [
            'DONWOK',
            'COMANDA',
            'ORDEN #'.$order->getOrderNumber(),
            $this->orderMeta($order),
            str_repeat('-', 32),
        ];

        foreach ($order->getItems() as $item) {
            $lines[] = sprintf('%d x %s', $item->getQuantity(), $item->getName());

            foreach ($this->groupComponents($item->getComponents()->toArray()) as $component) {
                $lines[] = sprintf('%s %s %s', $component['quantity'], $component['unit'], $component['productName']);
            }
        }

        $lines[] = str_repeat('-', 32);
        $lines[] = 'Estado: en preparacion';

        return new PrintTicket($lines);
    }

    public function customer(Order $order): PrintTicket
    {
        $lines = [
            'DONWOK',
            'DESPRENDIBLE CLIENTE',
            'ORDEN #'.$order->getOrderNumber(),
            $this->orderMeta($order),
        ];

        if ($order->getInvoice()) {
            $lines[] = 'FACTURA #'.$order->getInvoice()->getInvoiceNumber();
        }

        if ($order->getOrderType()->value === 'domicilio') {
            $lines[] = 'DATOS DE ENTREGA';
            $lines[] = 'Nombre: '.($order->getCustomerName() ?: '-');
            $lines[] = 'Telefono: '.($order->getCustomerPhone() ?: '-');
            $lines[] = 'Direccion: '.($order->getDeliveryAddress() ?: '-');
        }

        $lines[] = str_repeat('-', 32);

        foreach ($order->getItems() as $item) {
            $lines[] = sprintf('%d x %s  $ %s', $item->getQuantity(), $item->getName(), $this->money($item->getTotal()));

            foreach ($this->groupComponents($item->getComponents()->toArray()) as $component) {
                $lines[] = sprintf('%s %s %s  $ %s', $component['quantity'], $component['unit'], $component['productName'], $this->money((string) $component['total']));
            }
        }

        $lines[] = str_repeat('-', 32);
        $lines[] = 'TOTAL A PAGAR                 $ '.$this->money($order->getPayableTotal());

        if ($order->isInvoiced()) {
            $lines[] = 'Detalle del pago';
            $lines[] = 'Efectivo: $ '.$this->money($order->getCashAmount() ?? '0');
            $lines[] = 'Digital: $ '.$this->money($order->getDigitalAmount() ?? '0');

            if ((float) ($order->getCashAmount() ?? 0) > 0) {
                $lines[] = 'Recibido: $ '.$this->money($order->getCashReceived() ?? $order->getCashAmount() ?? '0');
                $lines[] = 'Cambio: $ '.$this->money($order->getChangeDue() ?? '0');
            }

            $lines[] = 'Tipo: '.strtoupper(str_replace('_', ' ', $order->getOrderType()->value));

            foreach ($order->getPaymentAllocations() as $allocation) {
                $lines[] = sprintf('%s: $ %s', $allocation->getCashBox()?->getName() ?? 'Caja', $this->money($allocation->getAmount()));
            }

            if ($order->getPaymentReceiptKey() && (float) ($order->getDigitalAmount() ?? 0) > 0) {
                $lines[] = 'Comprobante digital adjunto';
            }
        }

        $lines[] = '';
        $lines[] = 'Gracias por tu compra.';

        return new PrintTicket($lines);
    }

    public function invoice(Order $order, ?Invoice $invoice = null): PrintTicket
    {
        $customerTicket = $this->customer($order);
        $lines = $customerTicket->lines;
        $lines[1] = 'FACTURA DE VENTA';
        $lines[2] = 'FACTURA #'.($invoice?->getInvoiceNumber() ?? $order->getInvoice()?->getInvoiceNumber() ?? '-');
        array_splice($lines, 3, 0, ['ORDEN #'.$order->getOrderNumber()]);

        return new PrintTicket($lines, true, (float) ($order->getCashAmount() ?? '0') > 0);
    }

    private function orderMeta(Order $order): string
    {
        $type = ucfirst(str_replace('_', ' ', $order->getOrderType()->value));
        $extra = $order->getTableNumber() ? ' · Mesa: '.$order->getTableNumber() : '';

        return sprintf('%s · %s%s', $type, $order->getCustomerName() ?: 'Cliente', $extra);
    }

    private function money(string $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    /** @param list<object> $components */
    private function groupComponents(array $components): array
    {
        $grouped = [];

        foreach ($components as $component) {
            $key = sprintf('%s|%s|%d', $component->getProductName(), $component->getUnit(), $component->getQuantity());

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'productName' => $component->getProductName(),
                    'quantity' => 0,
                    'unit' => $component->getUnit(),
                    'total' => 0.0,
                ];
            }

            $grouped[$key]['quantity'] += $component->getQuantity();
            $grouped[$key]['total'] += (float) $component->getSalePrice();
        }

        return array_values($grouped);
    }
}
