# Haste StringUtil

This utility class is designed to ease string handling.


## Examples ##

### Flatten an array to only string values ###

This is most useful to convert values to simple tokens. 
Simple tokens can only handle string values and have special support for if/else constructs.


```php
<?php

$value = array('foo', 'bar');
$data  = array();

\Haste\Util\StringUtil::flatten($value, 'prefix', $data);

// Result
$data = array(
    'prefix_foo' => '1',
    'prefix_bar' => '1',
    'prefix' => 'foo, bar',
);
```


```php
<?php

$value = array('foo' => 'bar');
$data  = array();

\Haste\Util\StringUtil::flatten($value, 'prefix', $data);

// Result
$data = array(
    'prefix_foo' => 'bar',
    'prefix' => '',
);
```
