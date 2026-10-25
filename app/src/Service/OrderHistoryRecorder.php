<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderHistory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class OrderHistoryRecorder
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function record(
        Order $order,
        User $user,
        string $action,
        array $details = [],
        ?string $previousStatus = null,
        ?string $newStatus = null,
    ): OrderHistory {
        $entry = (new OrderHistory())
            ->setOrder($order)
            ->setPerformedBy($user)
            ->setAction($action)
            ->setDetails($details)
            ->setPreviousStatus($previousStatus)
            ->setNewStatus($newStatus);

        $order->addHistory($entry);
        $this->entityManager->persist($entry);

        return $entry;
    }
}
