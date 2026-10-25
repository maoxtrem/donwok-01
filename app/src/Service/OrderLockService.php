<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Exception\OrderLockedException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class OrderLockService
{
    private const int LOCK_TTL_SECONDS = 300;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function acquire(Order $order, User $user): void
    {
        $this->assertNotInvoiced($order);
        $now = new DateTimeImmutable();

        if ($this->hasActiveLock($order, $now) && !$this->isLockedBy($order, $user)) {
            throw new OrderLockedException(sprintf('La orden #%d está siendo editada por otro usuario.', $order->getOrderNumber()));
        }

        $order->setLockedBy($user)->setLockedAt($now);
        $this->entityManager->flush();
    }

    public function assertHeldBy(Order $order, User $user): void
    {
        $this->assertNotInvoiced($order);

        if (!$this->isLockedBy($order, $user) || !$this->hasActiveLock($order, new DateTimeImmutable())) {
            throw new OrderLockedException('La orden no está bloqueada por el usuario actual o el bloqueo venció.');
        }
    }

    public function release(Order $order, User $user): void
    {
        if ($order->getLockedBy() !== null && !$this->isLockedBy($order, $user) && !$this->isAdmin($user)) {
            throw new OrderLockedException('Solo quien tomó el bloqueo o un administrador puede liberarlo.');
        }

        $order->setLockedBy(null)->setLockedAt(null);
        $this->entityManager->flush();
    }

    public function isLockedByOther(Order $order, User $user): bool
    {
        return $this->hasActiveLock($order, new DateTimeImmutable()) && !$this->isLockedBy($order, $user);
    }

    private function assertNotInvoiced(Order $order): void
    {
        if ($order->isInvoiced()) {
            throw new InvalidArgumentException('Las órdenes facturadas no se pueden editar.');
        }
    }

    private function hasActiveLock(Order $order, DateTimeImmutable $now): bool
    {
        $lockedAt = $order->getLockedAt();

        return $order->getLockedBy() !== null
            && $lockedAt !== null
            && $lockedAt->modify(sprintf('+%d seconds', self::LOCK_TTL_SECONDS)) > $now;
    }

    private function isLockedBy(Order $order, User $user): bool
    {
        return $order->getLockedBy()?->getId() === $user->getId();
    }

    private function isAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
