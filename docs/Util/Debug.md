# Haste Debug

This utility class is designed to ease debug output.


## Examples ##

### Get uncompressed files ###

This method will remove `.min` from the file path if debug mode is active.

```php
<?php

$GLOBALS['TL_JAVASCRIPT'][] = \Haste\Util\Debug::uncompressedFile('path/to/file.min.js');
```


### Add message to console ###

Using this method, you can add custom messages to the Contao debug console.

```php
<?php

\Haste\Util\Debug::addToConsole(print_r($myVar, true), 'my_group');
```
