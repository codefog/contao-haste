<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\EventListener;

use Codefog\HasteBundle\Attribute\DoctrineOrmUndo;
use Codefog\HasteBundle\Attribute\DoctrineOrmVersion;
use Codefog\HasteBundle\DoctrineOrmHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class DoctrineOrmListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly DoctrineOrmHelper $helper,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove,
            Events::preUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handlePostUpdateRelations($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handlePostUpdateRelations($args);
        $this->handlePostUpdateVersions($args);
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->handlePreRemoveUndo($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->handlePreUpdateVersions($args);
    }

    private function handlePostUpdateRelations(LifecycleEventArgs $args): void
    {
        if (!$this->helper->hasEntityRelatedValues($args->getObject())) {
            return;
        }

        $relatedFields = $args->getObjectManager()->getClassMetadata($args->getObject()::class)->getAssociationNames();

        foreach ($relatedFields as $relatedField) {
            $this->helper->updateRelatedValues($args->getObjectManager(), $args->getObject(), $relatedField);
        }
    }

    private function handlePreRemoveUndo(LifecycleEventArgs $args): void
    {
        if (null === $this->getAttribute($args->getObject(), DoctrineOrmUndo::class)) {
            return;
        }

        $this->helper->storeObjectUndo($args->getObjectManager(), $args->getObject());
    }

    private function handlePreUpdateVersions(LifecycleEventArgs $args): void
    {
        if (($attribute = $this->getAttribute($args->getObject(), DoctrineOrmVersion::class)) === null) {
            return;
        }

        $attributeArguments = $attribute->getArguments();

        $this->helper->createObjectVersion(
            $args->getObjectManager(),
            $args->getObject(),
            $attributeArguments['editRouteParams'] ?? [],
        );
    }

    private function handlePostUpdateVersions(LifecycleEventArgs $args): void
    {
        if (null === $this->getAttribute($args->getObject(), DoctrineOrmVersion::class)) {
            return;
        }

        $this->helper->saveObjectVersion($args->getObject());
    }

    private function getAttribute(object $object, string $attribute): \ReflectionAttribute|null
    {
        $reflection = new \ReflectionClass($object);
        $reflectionAttributes = $reflection->getAttributes($attribute);

        return $reflectionAttributes[0] ?? null;
    }
}
