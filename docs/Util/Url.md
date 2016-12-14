# Haste Url

This utility class is designed to ease url handling.


## About the URL parameter ##

The `$varUrl` method argument can be one of three possible types:

1. If the value is `null`, the current URL is used (`\Environment::get('request')`).

2. If the value is `numeric`, Haste tries to find the page model with given ID
   and generates it's frontend URL. If the page is not found, an
   `\InvalidArgumentException` is thrown. Query parameters on the current URL
   are merged into the new URL.

3. If the value is a `string`, it's assumed to be a valid URL and used directly.


## Examples ##


### Adding a query string ###

Adding a query string is as simple as passing it as a string argument.
Haste will correctly add ampersand or question mark to the URL as needed.

```php
$url = \Haste\Util\Url::addQueryString('foo=bar');
```

You can also add multiple query strings with ease:

```php
$url = \Haste\Util\Url::addQueryString('foo1=bar1&foo2=bar2');
```


### Removing query string by name ###

The `removeQueryString` method accepts an array of query parameters to remove.

```php
$url = \Haste\Util\Url::removeQueryString(['foo', 'bar']);
```


### Removing query string using a callback ###

For more complex needs, query parameters can be removed using a callback.
The callback must return boolean true to keep the query parameters.
This method behaves similar to [`array_filter`](https://php.net/array_filter)
with flag `ARRAY_FILTER_USE_BOTH`.

```php
$url = \Haste\Util\Url::removeQueryStringCallback(
    function ($value, $key) {
        // Remove keys that start with "foo_"
        return strpos($key, 'foo_') !== 0;
    }
);
```
