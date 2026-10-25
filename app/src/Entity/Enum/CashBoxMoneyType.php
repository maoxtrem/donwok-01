<?php

namespace App\Entity\Enum;

enum CashBoxMoneyType: string
{
    case CASH = 'efectivo';
    case DIGITAL = 'digital';
}
