<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Throwable;

final class OrderRealtimePublisher
{
    public function __construct(private readonly HubInterface $hub, private readonly string $topicBase)
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
        $ordersTopic = rtrim($this->topicBase, '/').'/topics/orders';

        try {
            $this->hub->publish(new Update([
                $ordersTopic,
                sprintf('%s/%d', $ordersTopic, $order->getId()),
            ], $data));
        } catch (Throwable) {
            // Mercure no debe impedir guardar una orden si está temporalmente fuera de servicio.
        }
    }
}
