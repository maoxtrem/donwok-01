<?php

namespace App\Service;

use App\Entity\CashClosure;
use App\Entity\CashLedgerEntry;
use App\Entity\CashMovement;
use App\Entity\Invoice;
use App\Printing\PrintTicket;

final class FinancialTicketFactory
{
    public function ledgerEntry(CashLedgerEntry $entry): PrintTicket
    {
        $box = $entry->getCashBox();

        return new PrintTicket([
            'DONWOK',
            $this->entryTitle($entry->getType()),
            'CAJA: '.($box?->getName() ?? '-'),
            'FECHA: '.$this->date($entry->getCreatedAt()),
            'MONTO: COP '.$this->money($entry->getAmount()),
            'ESTADO: '.strtoupper(str_replace('_', ' ', $entry->getStatus())),
            'DETALLE: '.$entry->getDescription(),
            str_repeat('-', 32),
        ]);
    }

    public function movement(CashMovement $movement): PrintTicket
    {
        return new PrintTicket([
            'DONWOK',
            'TRASLADO ENTRE CAJAS',
            'ORIGEN: '.($movement->getSourceBox()?->getName() ?? '-'),
            'DESTINO: '.($movement->getDestinationBox()?->getName() ?? '-'),
            'FECHA: '.$this->date($movement->getCreatedAt()),
            'MONTO: COP '.$this->money($movement->getAmount()),
            'MOTIVO: '.$movement->getReason(),
            str_repeat('-', 32),
        ]);
    }

    public function closure(CashClosure $closure): PrintTicket
    {
        $lines = [
            'DONWOK',
            'CIERRE DE CAJA',
            'CIERRE #'.($closure->getId() ?? '-'),
            'FECHA: '.$this->date($closure->getCreatedAt()),
            'ESPERADO: COP '.$this->money($closure->getExpectedTotal()),
            'CONTADO: COP '.$this->money($closure->getCountedTotal()),
            'DIFERENCIA: COP '.$this->money($closure->getDifferenceTotal()),
        ];

        foreach ($closure->getLines() as $line) {
            $lines[] = sprintf('%s: esperado %s / contado %s', $line->getCashBox()?->getName() ?? 'Caja', $this->money($line->getExpected()), $this->money($line->getCounted()));
        }

        if ($closure->getObservations()) {
            $lines[] = 'OBSERVACIONES: '.$closure->getObservations();
        }

        $lines[] = str_repeat('-', 32);

        return new PrintTicket($lines);
    }

    public function closureInvoice(Invoice $invoice): PrintTicket
    {
        $order = $invoice->getOrder();
        if ($order === null) {
            return new PrintTicket([
                'FACTURA #'.str_pad((string) $invoice->getInvoiceNumber(), 5, '0', STR_PAD_LEFT),
                'ORDEN NO DISPONIBLE',
            ], compact: true);
        }

        $lines = [
            'FACTURA #'.str_pad((string) $invoice->getInvoiceNumber(), 5, '0', STR_PAD_LEFT),
            sprintf('ORDEN #%d · %s', $order->getOrderNumber(), $this->date($invoice->getIssuedAt())),
            str_repeat('-', 32),
        ];

        foreach ($order->getItems() as $item) {
            $isComposite = $item->getSourceType() !== 'product';
            if ($isComposite) {
                $lines[] = sprintf('%d x %s', $item->getQuantity(), $item->getName());
            }

            $components = $item->getComponents()->toArray();
            if ($components === []) {
                $lines[] = sprintf('%d x %s $ %s', $item->getQuantity(), $item->getName(), $this->money($item->getTotal()));
                continue;
            }

            foreach ($components as $component) {
                $quantity = $component->getQuantity();
                $componentTotal = $component->getSalePrice();
                if ($item->getQuantity() > 1) {
                    $quantity *= $item->getQuantity();
                    $componentTotal = $this->multiplyMoney($componentTotal, $item->getQuantity());
                }
                $lines[] = sprintf(
                    '  %d %s %s $ %s',
                    $quantity,
                    $component->getUnit(),
                    $component->getProductName(),
                    $this->money($componentTotal),
                );
            }
        }

        $lines[] = str_repeat('-', 32);

        return new PrintTicket($lines, compact: true);
    }

    private function entryTitle(string $type): string
    {
        return match ($type) {
            'venta' => 'INGRESO POR VENTA',
            'egreso' => 'EGRESO',
            'cierre_salida' => 'SALIDA POR CIERRE',
            'traslado_entrada' => 'INGRESO POR TRASLADO',
            'traslado_salida' => 'SALIDA POR TRASLADO',
            default => strtoupper(str_replace('_', ' ', $type)),
        };
    }

    private function date(?\DateTimeImmutable $date): string
    {
        return $date?->setTimezone(new \DateTimeZone('America/Bogota'))->format('Y-m-d H:i:s') ?? '-';
    }

    private function money(string $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private function multiplyMoney(string $value, int $quantity): string
    {
        return number_format((float) $value * $quantity, 2, '.', '');
    }
}
