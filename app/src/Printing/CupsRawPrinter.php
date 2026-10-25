<?php

namespace App\Printing;

use RuntimeException;
use Symfony\Component\Process\Process;

final class CupsRawPrinter implements PrinterInterface
{
    public function __construct(
        private readonly string $printerName,
        private readonly EscPos80mmFormatter $formatter,
    ) {
    }

    public function print(PrintTicket $ticket): void
    {
        $this->printSequence([$ticket]);
    }

    /** @param list<PrintTicket> $tickets */
    public function printSequence(array $tickets): void
    {
        if (trim($this->printerName) === '') {
            throw new RuntimeException('No hay una impresora configurada. Define PRINTER_NAME.');
        }

        // Igual que el proyecto de origen: CUPS recibe el ticket ESC/POS por
        // STDIN y lo envía sin reinterpretarlo mediante la opción raw.
        $process = new Process(
            ['lp', '-o', 'raw', '-d', $this->printerName],
            null,
            null,
            $this->formatter->formatSequence($tickets),
        );
        $process->setTimeout(15);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());
            throw new RuntimeException(sprintf('No se pudo imprimir en "%s": %s', $this->printerName, $error));
        }
    }
}
