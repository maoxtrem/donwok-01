<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Throwable;

final class OrderRealtimePublisher
{
    public function __construct(private readonly HubInterface $hub)
    {
    }

    public function orderChanged(Order $order, string $event): void
    {
        $data = json_encode([
            'event' => $event,
            'orderId' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'orderType' => $order->getOrderType()->value,
            'operationalStatus' => $order->getOperationalStatus()->value,
            'billingStatus' => $order->getBillingStatus()->value,
        ], JSON_THROW_ON_ERROR);

        try {
            $this->hub->publish(new Update([
                'orders',
                sprintf('orders/%d', $order->getId()),
            ], $data));
        } catch (Throwable) {
            // Mercure no debe impedir guardar una orden si está temporalmente fuera de servicio.
        }
    }
}
