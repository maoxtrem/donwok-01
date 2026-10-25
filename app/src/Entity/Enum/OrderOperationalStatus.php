<?php

namespace App\Entity\Enum;

enum OrderOperationalStatus: string
{
    case PENDING = 'pendiente';
    case PREPARING = 'en_preparacion';
    case PREPARED = 'preparada';
    case SERVED = 'servido';
    case PACKED = 'empacada';
    case DELIVERED = 'entregada';
    case REJECTED = 'rechazada';
}
