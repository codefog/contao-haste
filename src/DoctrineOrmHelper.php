<?php

namespace Codefog\HasteBundle;

use Codefog\HasteBundle\Attribute\DoctrineOrmVersion;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Versions;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;

class DoctrineOrmHelper
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly DcaRelationsManager $dcaRelationsManager,
    ) {}

    /**
     * Store the version "undo" data for the given object.
     */
    public function storeObjectVersion(ObjectManager $objectManager, object $object): void
    {
        $reflection = new \ReflectionClass($object);

        if (count($reflection->getAttributes(DoctrineOrmVersion::class)) === 0) {
            return;
        }

        $table = $objectManager->getClassMetadata($object::class)->getTableName();

        $this->framework->initialize();

        $versions = new Versions($table, $object->getId());
        $versions->initialize();
        $versions->create(true);
    }

    /**
     * Store the "undo" data for the given object.
     */
    public function storeObjectUndo(ObjectManager $objectManager, object $object): void
    {
        $reflection = new \ReflectionClass($object);

        if (count($reflection->getAttributes(DoctrineOrmUndo::class)) === 0) {
            return;
        }

        $table = $objectManager->getClassMetadata($object::class)->getTableName();

        $this->connection->insert('tl_undo', [
            'pid' => 0,
            'tstamp' => time(),
            'fromTable' => $table,
            'query' => sprintf('DELETE FROM %s WHERE id=%d', $table, $object->getId()),
            'affectedRows' => 1,
            'data' => serialize([
                $table => $this->connection->fetchAllAssociative("SELECT * FROM $table WHERE id=?", [$object->getId()]),
            ]),
        ]);

        $this->dcaRelationsManager->deleteRelatedRecordsWithUndo($table, $object->getId(), $this->connection->lastInsertId());
    }
}
