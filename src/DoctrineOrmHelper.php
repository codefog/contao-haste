<?php

declare(strict_types=1);

namespace Codefog\HasteBundle;

use Codefog\HasteBundle\Model\DcaRelationsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\Versions;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DoctrineOrmHelper
{
    private array $entityRelatedValues = [];

    /**
     * @var array<Versions>
     */
    private array $entityVersions = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly DcaRelationsManager $dcaRelationsManager,
        private readonly RouterInterface $router,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * Add the entity related values.
     */
    public function addEntityRelatedValues(object $entity, string $field, array $values): void
    {
        $this->entityRelatedValues[$this->getEntityUniqueId($entity)][$field] = $values;
    }

    /**
     * Has the entity related values.
     */
    public function hasEntityRelatedValues(object $entity): bool
    {
        if (!method_exists($entity, 'getId')) {
            return false;
        }

        return isset($this->entityRelatedValues[$this->getEntityUniqueId($entity)]);
    }

    /**
     * Update the related values, if any.
     *
     * @template T of object
     *
     * @param T $entity
     */
    public function updateRelatedValues(ObjectManager $objectManager, object $entity, string $field): void
    {
        $relatedValues = $this->entityRelatedValues[$this->getEntityUniqueId($entity)] ?? [];

        if (!isset($relatedValues[$field])) {
            return;
        }

        $this->framework->initialize();

        /** @var ClassMetadataInfo<T> $metadata */
        $metadata = $objectManager->getClassMetadata($entity::class);

        DcaRelationsModel::setRelatedValues(
            $metadata->getTableName(),
            $field,
            $entity->getId(),
            $relatedValues[$field],
        );
    }

    /**
     * Create the object version "undo" data for the given object.
     *
     * @template T of object
     *
     * @param T $entity
     */
    public function createObjectVersion(ObjectManager $objectManager, object $entity, array $editRouteParams = []): void
    {
        $this->framework->initialize();

        /** @var ClassMetadataInfo<T> $metadata */
        $metadata = $objectManager->getClassMetadata($entity::class);

        $versions = new Versions($metadata->getTableName(), $entity->getId());

        // Set the frontend user, if any
        if (($user = $this->tokenStorage->getToken()?->getUser()) instanceof FrontendUser) {
            $versions->setUsername($user->username);
            $versions->setUserId(0);
        }

        // Set the edit URL, if any
        if (\count($editRouteParams) > 0) {
            $editRouteParams['id'] ??= '%s';
            $editRouteParams['act'] ??= 'edit';
            $editRouteParams['rt'] ??= '1';

            $versions->setEditUrl($this->router->generate('contao_backend', $editRouteParams));
        }

        $versions->initialize();

        $this->entityVersions[$this->getEntityUniqueId($entity)] = $versions;
    }

    /**
     * Save the entity version.
     */
    public function saveObjectVersion(object $entity): void
    {
        $uniqueId = $this->getEntityUniqueId($entity);

        if (!isset($this->entityVersions[$uniqueId])) {
            throw new \RuntimeException(\sprintf('The entity with "%s" identifier has no \Contao\Versions instance', $uniqueId));
        }

        $version = $this->entityVersions[$uniqueId];

        try {
            $version->create();
        } catch (\LogicException) {
            $version->setUsername('');
            $version->create();
        }
    }

    /**
     * Store the "undo" data for the given entity.
     *
     * @template T of object
     *
     * @param T $entity
     */
    public function storeObjectUndo(ObjectManager $objectManager, object $entity): void
    {
        /** @var ClassMetadataInfo<T> $metadata */
        $metadata = $objectManager->getClassMetadata($entity::class);

        $table = $metadata->getTableName();

        $this->connection->insert('tl_undo', [
            'pid' => 0,
            'tstamp' => time(),
            'fromTable' => $table,
            'query' => \sprintf('DELETE FROM %s WHERE id=%d', $table, $entity->getId()),
            'affectedRows' => 1,
            'data' => serialize([
                $table => $this->connection->fetchAllAssociative("SELECT * FROM $table WHERE id=?", [$entity->getId()]),
            ]),
        ]);

        $this->dcaRelationsManager->deleteRelatedRecordsWithUndo($table, $entity->getId(), (int) $this->connection->lastInsertId());
    }

    /**
     * Get the entity unique ID.
     */
    private function getEntityUniqueId(object $entity): string
    {
        return $entity::class.'::'.spl_object_id($entity);
    }
}
