<?php

use Codefog\HasteBundle\UndoManager;

// Add the "haste_undo" operation to "undo" module
$GLOBALS['BE_MOD']['system']['undo']['haste_undo'] = [UndoManager::class, 'onUndoCallback'];
