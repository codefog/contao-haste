<?php

namespace Codefog\HasteBundle\EventListener;

use Codefog\HasteBundle\DoctrineOrmHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class DoctrineOrmListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly DoctrineOrmHelper $helper,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::preRemove,
            Events::preUpdate,
        ];
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->helper->storeObjectUndo($args->getObjectManager(), $args->getObject());
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->helper->storeObjectVersion($args->getObjectManager(), $args->getObject());
    }
}
