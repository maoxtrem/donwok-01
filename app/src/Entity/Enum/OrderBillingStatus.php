<?php

namespace App\Entity\Enum;

enum OrderBillingStatus: string
{
    case PENDING = 'sin_facturar';
    case INVOICED = 'facturada';
    case ANNULLED = 'anulada';
}
