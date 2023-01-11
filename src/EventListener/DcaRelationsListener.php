<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\EventListener;

use Codefog\HasteBundle\DcaRelationsManager;
use Codefog\HasteBundle\Event\UndoEvent;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class DcaRelationsListener
{
    public function __construct(private readonly DcaRelationsManager $dcaRelations,)
    {
    }

    #[AsEventListener(event: UndoEvent::NAME)]
    public function onUndo(UndoEvent $event): void
    {
        $this->dcaRelations->undoRelations($event->getHasteData(), $event->getId(), $event->getTable(), $event->getRow());
    }

    #[AsEventListener]
    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        $this->dcaRelations->appendToSchema($event->getSchema());
    }
}
