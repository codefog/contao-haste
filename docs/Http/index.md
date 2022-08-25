# Haste Http

Various classes to ease HTTP handling.


## Examples ##

### Sending a general 200 OK response ###

```php
<?php

$objResponse = new \Http\Response\Response();
$objResponse->send();
```

### Sending a 400 Bad Request response with foobar content ###

```php
<?php

$objResponse = new \Http\Response\Response('Foobar content', 400);
$objResponse->send();
```

### Plaintext, HTML, XML and JSON ###

You can use different types of responses.

```php
<?php

$objResponse = new \Http\Response\Response();
$objResponse = new \Http\Response\JsonResponse();
$objResponse = new \Http\Response\HtmlResponse();
$objResponse = new \Http\Response\XmlResponse();
```

### Redirecting ###

Use the `RedirectResponse` to send a visitor to another URL.
Defaults to HTTP status code 301 (Moved Permanently).

```php
<?php

$objResponse = new \Http\Response\RedirectResponse('http://example.com', 303);
$objResponse->send();
```
