<?php

namespace Codefog\HasteBundle\EventListener;

use Codefog\HasteBundle\Attribute\DoctrineOrmUndo;
use Codefog\HasteBundle\Attribute\DoctrineOrmVersion;
use Codefog\HasteBundle\DoctrineOrmHelper;
use Contao\Versions;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class DoctrineOrmListener implements EventSubscriberInterface
{
    /** @var array|Versions[] */
    private array $objectWithVersions = [];

    public function __construct(
        private readonly DoctrineOrmHelper $helper,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postUpdate,
            Events::preRemove,
            Events::preUpdate,
        ];
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (!$this->hasAttribute($args->getObject(), DoctrineOrmVersion::class)) {
            return;
        }

        $uniqueId = $this->getObjectUniqueId($args);

        if (isset($this->objectWithVersions[$uniqueId])) {
            $this->helper->saveObjectVersion($this->objectWithVersions[$uniqueId]);
        }
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        if (!$this->hasAttribute($args->getObject(), DoctrineOrmUndo::class)) {
            return;
        }

        $this->helper->storeObjectUndo($args->getObjectManager(), $args->getObject());
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $reflection = new \ReflectionClass($args->getObject());
        $attributes = $reflection->getAttributes(DoctrineOrmVersion::class);

        if (count($attributes) === 0) {
            return;
        }

        $attributeArguments = $attributes[0]->getArguments();

        $version = $this->helper->createObjectVersion(
            $args->getObjectManager(),
            $args->getObject(),
            $attributeArguments['editRouteParams'] ?? [],
        );

        if ($version !== null) {
            $this->objectWithVersions[$this->getObjectUniqueId($args)] = $version;
        }
    }

    private function hasAttribute(object $object, string $attribute): bool
    {
        $reflection = new \ReflectionClass($object);

        return count($reflection->getAttributes($attribute)) > 0;
    }

    private function getObjectUniqueId(LifecycleEventArgs $args): string
    {
        return $args->getObjectManager()->getClassMetadata(get_class($args->getObject()))->getTableName() . '.' . $args->getObject()->getId();
    }
}
