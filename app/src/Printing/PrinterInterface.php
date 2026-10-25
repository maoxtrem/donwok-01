<?php

namespace App\Printing;

interface PrinterInterface
{
    public function print(PrintTicket $ticket): void;

    /** @param list<PrintTicket> $tickets */
    public function printSequence(array $tickets): void;
}
