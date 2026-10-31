<?php

namespace App\Printing;

final readonly class PrintTicket
{
    /** @param list<string> $lines */
    public function __construct(
        public array $lines,
        public bool $cut = true,
        public bool $openDrawer = false,
        public bool $compact = false,
    ) {
    }
}
