<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Invoice;
use App\Printing\PrintTicket;

final class OrderTicketFactory
{
    public function __construct(private readonly PrintTemplateProvider $templates)
    {
    }

    public function kitchen(Order $order): PrintTicket
    {
        $template = $this->templates->get('kitchen');
        $lines = [$template->getBrand(), $template->getTitle(), $template->showsOrderNumber() ? 'ORDEN #'.$order->getOrderNumber() : ''];
        array_push($lines, ...$this->orderMeta($order, $template));
        $lines[] = str_repeat('-', 48);

        foreach ($order->getItems() as $item) {
            $itemLine = sprintf('%d x %s', $item->getQuantity(), $item->getName());
            if ($template->showsPrices()) {
                $itemLine .= '  $ '.$this->money($item->getTotal());
            }
            $lines[] = $itemLine;

            if ($template->showsComponents()) {
                foreach ($this->groupComponents($item->getComponents()->toArray()) as $component) {
                    $componentLine = sprintf('%s %s %s', $component['quantity'], $component['unit'], $component['productName']);
                    if ($template->showsPrices()) {
                        $componentLine .= '  $ '.$this->money((string) $component['total']);
                    }
                    $lines[] = $componentLine;
                }
            }
        }

        $lines[] = str_repeat('-', 48);
        if ($template->getFooter() !== '') {
            $lines[] = $template->getFooter();
        }

        return new PrintTicket($lines);
    }

    public function customer(Order $order): PrintTicket
    {
        $template = $this->templates->get('customer');
        $lines = [$template->getBrand(), $template->getTitle(), $template->showsOrderNumber() ? 'ORDEN #'.$order->getOrderNumber() : ''];
        array_push($lines, ...$this->orderMeta($order, $template));

        if ($order->getInvoice()) {
            $lines[] = 'FACTURA #'.$order->getInvoice()->getInvoiceNumber();
        }

        if ($template->showsDeliveryDetails() && $order->getOrderType()->value === 'domicilio') {
            $lines[] = 'DATOS DE ENTREGA';
            if ($template->showsCustomer()) {
                $lines[] = 'Nombre: '.($order->getCustomerName() ?: '-');
            }
            $lines[] = 'Telefono: '.($order->getCustomerPhone() ?: '-');
            $lines[] = 'Direccion: '.($order->getDeliveryAddress() ?: '-');
        }

        $lines[] = str_repeat('-', 48);

        foreach ($order->getItems() as $item) {
            $itemLine = sprintf('%d x %s', $item->getQuantity(), $item->getName());
            if ($template->showsPrices()) {
                $itemLine .= '  $ '.$this->money($item->getTotal());
            }
            $lines[] = $itemLine;

            if ($template->showsComponents()) {
                foreach ($this->groupComponents($item->getComponents()->toArray()) as $component) {
                    $componentLine = sprintf('%s %s %s', $component['quantity'], $component['unit'], $component['productName']);
                    if ($template->showsPrices()) {
                        $componentLine .= '  $ '.$this->money((string) $component['total']);
                    }
                    $lines[] = $componentLine;
                }
            }
        }

        if ($template->showsTotal()) {
            $lines[] = str_repeat('-', 48);
            $lines[] = 'TOTAL A PAGAR                 $ '.$this->money($order->getPayableTotal());
        }

        if ($template->showsPaymentDetails() && $order->isInvoiced()) {
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

        if ($template->getFooter() !== '') {
            $lines[] = '';
            $lines[] = $template->getFooter();
        }

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

    /** @return list<string> */
    private function orderMeta(Order $order, \App\Entity\PrintTemplate $template): array
    {
        $lines = [];
        if ($template->showsDate()) {
            $lines[] = $order->getCreatedAt()?->format('d/m/Y H:i') ?? $order->getOrderDate()->format('d/m/Y');
        }
        if ($template->showsOrderType()) {
            $lines[] = 'Tipo: '.strtoupper(str_replace('_', ' ', $order->getOrderType()->value));
        }
        if ($template->showsTableNumber() && $order->getTableNumber()) {
            $lines[] = 'Mesa: '.$order->getTableNumber();
        }
        if ($template->showsCustomer() && $order->getCustomerName()) {
            $lines[] = 'Cliente: '.$order->getCustomerName();
        }

        return $lines;
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
