<?php

// Replace the "undo" button href
$GLOBALS['TL_DCA']['tl_undo']['list']['operations']['undo']['button_callback'] = [\Codefog\HasteBundle\UndoManager::class, 'button'];

// Add fields to tl_undo
$GLOBALS['TL_DCA']['tl_undo']['fields']['haste_data'] = [
    'eval' => ['doNotShow' => true],
    'sql' => ['type' => 'blob', 'notnull' => false],
];
