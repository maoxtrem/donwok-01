<?php

namespace App\Printing;

final readonly class PrintService
{
    public function __construct(private PrinterInterface $printer)
    {
    }

    public function print(PrintTicket $ticket): void
    {
        $this->printer->print($ticket);
    }

    /** @param list<PrintTicket> $tickets */
    public function printSequence(array $tickets): void
    {
        $this->printer->printSequence($tickets);
    }
}
