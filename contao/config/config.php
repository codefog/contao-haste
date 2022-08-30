<?php

// Add the "haste_undo" operation to "undo" module
$GLOBALS['BE_MOD']['system']['undo']['haste_undo'] = [\Codefog\HasteBundle\UndoManager::class, 'onUndoCallback'];
