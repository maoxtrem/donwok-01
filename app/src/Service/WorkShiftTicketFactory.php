<?php

namespace App\Service;

use App\Entity\WorkShift;
use App\Printing\PrintTicket;

final class WorkShiftTicketFactory
{
    /**
     * @return array{ticket: PrintTicket, workedMinutes: int, amountToPay: string}
     */
    public function manual(string $name, \DateTimeImmutable $startedAt, \DateTimeImmutable $closedAt, string $hourlyRate): array
    {
        $workedMinutes = intdiv(max(0, $closedAt->getTimestamp() - $startedAt->getTimestamp()), 60);
        $exact = $workedMinutes * ((float) $hourlyRate / 60);
        $amountToPay = number_format(ceil($exact / 50) * 50, 2, '.', '');
        $hours = intdiv($workedMinutes, 60);
        $minutes = $workedMinutes % 60;

        $ticket = new PrintTicket([
            'DONWOK',
            'LIQUIDACION MANUAL',
            'NOMBRE: '.$name,
            'INICIO: '.$this->date($startedAt),
            'FIN: '.$this->date($closedAt),
            'VALOR HORA: COP '.$this->money($hourlyRate),
            sprintf('TIEMPO: %d H %02d MIN', $hours, $minutes),
            'MINUTOS: '.$workedMinutes,
            'TOTAL A PAGAR: COP '.$this->money($amountToPay),
            'COMPROBANTE MANUAL',
            str_repeat('-', 32),
        ]);

        return [
            'ticket' => $ticket,
            'workedMinutes' => $workedMinutes,
            'amountToPay' => $amountToPay,
        ];
    }

    public function started(WorkShift $shift): PrintTicket
    {
        $position = $shift->getPosition();

        return new PrintTicket([
            'DONWOK',
            'INICIO DE TURNO',
            $position?->getTitle() ?? 'PUESTO DE TRABAJO',
            'CODIGO PUESTO: '.($position?->getCode() ?? '-'),
            'INICIO: '.$this->date($shift->getStartedAt()),
            'VALOR HORA: COP '.$this->money($shift->getHourlyRate()),
            'CODIGO DE CIERRE: '.$shift->getPlainCloseCode(),
            'ESTADO: TURNO ACTIVO',
            str_repeat('-', 32),
        ]);
    }

    public function closed(WorkShift $shift): PrintTicket
    {
        $position = $shift->getPosition();

        return new PrintTicket([
            'DONWOK',
            'CIERRE DE TURNO',
            $position?->getTitle() ?? 'PUESTO DE TRABAJO',
            'CODIGO PUESTO: '.($position?->getCode() ?? '-'),
            'INICIO: '.$this->date($shift->getStartedAt()),
            'CIERRE: '.$this->date($shift->getClosedAt()),
            'VALOR HORA: COP '.$this->money($shift->getHourlyRate()),
            'MINUTOS: '.$shift->getWorkedMinutes(),
            'TOTAL A PAGAR: COP '.$this->money($shift->getAmountToPay() ?? '0'),
            str_repeat('-', 32),
        ]);
    }

    private function date(?\DateTimeImmutable $date): string
    {
        return $date?->setTimezone(new \DateTimeZone('America/Bogota'))->format('Y-m-d H:i:s') ?? '-';
    }

    private function money(string $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}
