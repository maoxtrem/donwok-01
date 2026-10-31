<?php

namespace App\Printing;

final class EscPos80mmFormatter
{
    private const INIT = "\x1b@";
    private const FEED = "\n";
    private const CUT = "\x1b\x64\x06\x1d\x56\x00";

    public function format(PrintTicket $ticket): string
    {
        return $this->formatSequence([$ticket]);
    }

    /** @param list<PrintTicket> $tickets */
    public function formatSequence(array $tickets): string
    {
        $output = self::INIT;

        foreach ($tickets as $index => $ticket) {
            $output .= $this->formatTicket($ticket);

            if ($index < count($tickets) - 1 || $ticket->cut) {
                if ($ticket->openDrawer) {
                    $output .= "\x1b\x70\x00\x19\xfa";
                }
                $output .= self::CUT;
            }
        }

        return $output;
    }

    private function formatTicket(PrintTicket $ticket): string
    {
        $output = '';

        foreach ($ticket->lines as $index => $line) {
            $line = $this->sanitize($line);

            if ($index === 0) {
                $output .= "\x1ba\x01\x1bE\x01\x1d!\x11".$line."\n";
                $output .= "\x1d!\x00\x1bE\x00";
                continue;
            }

            if ($index === 1) {
                $output .= "\x1ba\x01".$line."\n";
                continue;
            }

            if ($index === 2) {
                $output .= "\x1bE\x01\x1d!\x22".$line."\n";
                $output .= "\x1d!\x00\x1bE\x00\x1ba\x00";
                continue;
            }

            $output .= "\x1ba\x00".$line."\n";
        }

        return $output;
    }

    private function sanitize(string $line): string
    {
        $line = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $line) ?? '';

        // Most thermal printers are configured with a Latin code page. Keeping
        // printable UTF-8 here is safe for preview, while CUPS/printer config
        // remains responsible for the final character encoding.
        return iconv_substr($line, 0, 48, 'UTF-8') ?: '';
    }
}
