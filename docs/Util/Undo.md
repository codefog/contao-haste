# Haste Undo

This utility class is designed to help restore the data using the Restore module (tl_undo).

## Examples ##

First, you must register a ondelete_callback:

```php
$GLOBALS['TL_DCA']['tl_table']['config']['ondelete_callback'][] = array('tl_table', 'storeUndoData');

class tl_table
{
    public function storeUndoData(DataContainer $dc, $intUndoId)
    {
        $arrData = array('foo'=>'bar');
        \Haste\Util\Undo::add($intUndoId, 'my_data', $arrData);
    }
}
```

Then, to manually restore the data you have to register a hook:

```php
$GLOBALS['HASTE_HOOKS']['undoData'][] = array('tl_table', 'restoreMyData');

class tl_table
{
    /**
     * Restore my data
     * @param array
     * @param integer
     * @param string
     * @param array
     */
    public function restoreMyData($arrData, $intId, $strTable, $arrRow)
    {
        if (!$arrData['my_data']) {
            return;
        }

        // restore the data
    }
}
```
