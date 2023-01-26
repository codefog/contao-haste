<?php

namespace Codefog\HasteBundle;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\Versions;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class DoctrineOrmHelper
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly DcaRelationsManager $dcaRelationsManager,
        private readonly RouterInterface $router,
        private readonly Security $security,
    )
    {}

    /**
     * Create the object version "undo" data for the given object.
     */
    public function createObjectVersion(ObjectManager $objectManager, object $object, array $editRouteParams = []): ?Versions
    {
        $this->framework->initialize();

        $versions = new Versions($objectManager->getClassMetadata($object::class)->getTableName(), $object->getId());

        // Set the frontend user, if any
        if (($user = $this->security->getUser()) instanceof FrontendUser) {
            $versions->setUsername($user->username);
            $versions->setUserId(0);
        }

        // Set the edit URL, if any
        if (count($editRouteParams) > 0) {
            $editRouteParams['id'] ??= '%s';
            $editRouteParams['act'] ??= 'edit';
            $editRouteParams['rt'] ??= '1';

            $versions->setEditUrl($this->router->generate('contao_backend', $editRouteParams));
        }

        $versions->initialize();

        return $versions;
    }

    /**
     * Save the object version.
     */
    public function saveObjectVersion(Versions $versions): void
    {
        $versions->create();
    }

    /**
     * Store the "undo" data for the given object.
     */
    public function storeObjectUndo(ObjectManager $objectManager, object $object): void
    {
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
