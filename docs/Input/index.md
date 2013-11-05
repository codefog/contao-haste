# Haste Input

Various classes to improve input handling.


## Examples ##

### Simplified auto_item handling ###

```php
<?php

$strValue = \Haste\Input\Input::getAutoItem('items');
```

This will check if `auto_item` is activated on the given key,
and return it's value automatically.



### Use ID in an URL without having an alias ###

In this example, 12 is the ID of a news item:
http://example.com/item/12-news-title.html

```php
<?php

$intId = \Haste\Input\UrlId::get('items');
$objItem = \NewsModel::findByPk($intId);

if (null === $objItem) {
    // Generate 404 page
}

// Validate the news title in the URL
\Haste\Input\UrlId::validateName('items', $objItem->title);
```

If the given name is not correct, (e.g. it would be "the-news-title"), the
visitor is automatically redirected to the correct URL (using a 301 redirect).