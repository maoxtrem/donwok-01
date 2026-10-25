<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Security;

final class AuditUserSubscriber implements EventSubscriber
{
    public function __construct(private readonly Security $security)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof User || !$currentUser->isActive()) {
            return;
        }

        $entityManager = $event->getObjectManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (!method_exists($entity, 'setCreatedBy') || !method_exists($entity, 'setUpdatedBy')) {
                continue;
            }

            if ($entity->getCreatedBy() === null) {
                $entity->setCreatedBy($currentUser);
            }

            $entity->setUpdatedBy($currentUser);
            $this->recomputeChangeSet($entityManager, $entity);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!method_exists($entity, 'setUpdatedBy')) {
                continue;
            }

            $entity->setUpdatedBy($currentUser);
            $this->recomputeChangeSet($entityManager, $entity);
        }
    }

    private function recomputeChangeSet(EntityManagerInterface $entityManager, object $entity): void
    {
        $metadata = $entityManager->getClassMetadata($entity::class);
        $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($metadata, $entity);
    }
}
