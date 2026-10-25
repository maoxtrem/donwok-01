<?php

namespace App\Service;

use App\Entity\WorkShift;
use App\Printing\PrintTicket;

final class WorkShiftTicketFactory
{
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
