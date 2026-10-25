<?php

namespace App\Entity\Enum;

enum PaymentMethod: string
{
    case CASH = 'efectivo';
    case DIGITAL = 'digital';
    case MIXED = 'mixto';
}
