<?php

namespace App\Entity\Enum;

enum OrderType: string
{
    case TABLE = 'mesa';
    case TAKEAWAY = 'llevar';
    case DELIVERY = 'domicilio';
}
