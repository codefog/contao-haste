# Undo component

This component class is designed to help restore the data using the `Restore` module (`tl_undo`).


## Usage

First, you must register an `ondelete_callback` in your DCA:

```php
// DCA
$GLOBALS['TL_DCA']['tl_table']['config']['ondelete_callback'][] = [MyListener::class, 'onDeleteCallback'];

// Listener
use Codefog\HasteBundle\UndoManager;
use Contao\DataContainer;

class MyListener
{
    public function onDeleteCallback(DataContainer $dc, int $undoId): void
    {
        $this->undoManager->add($undoId, 'my_data', ['foo' => 'bar']);
    }
}
```

Then, to manually restore the data you have to subscribe to the event listener:

```php
use Codefog\HasteBundle\Event\UndoEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UndoEvent::NAME)]
class MyListener
{
    public function __invoke(UndoEvent $event)
    {
        // … do your magic …
    }
}
```
