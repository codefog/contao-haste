# UrlParser component

This component is designed to ease URL handling.


## Usage ##

The second method argument (`$url`) can be one of these possible types:

1. If the value is `null`, the current URL is used (`\Contao\Environment::get('requestUri')`).
2. If the value is a `string`, it's assumed to be a valid URL and used directly.

### Adding a query string ###

Adding a query string is as simple as passing it as a string argument.
Haste will correctly add an ampersand or question mark to the URL as needed.

```php
$this->urlParser->addQueryString('foo=bar');
```

You can also add multiple query strings with ease:

```php
$this->urlParser->addQueryString('foo1=bar1&foo2=bar2');
```


### Removing query string by name ###

The `removeQueryString` method accepts an array of query parameters to remove.

```php
$this->urlParser->removeQueryString(['foo', 'bar']);
```


### Removing query string using a callback ###

For more complex needs, query parameters can be removed using a callback.
The callback must return boolean true to keep the query parameters.
This method behaves similar to [`array_filter`](https://php.net/array_filter)
with flag `ARRAY_FILTER_USE_BOTH`.

```php
// Remove keys that start with "foo_"
$this->urlParser->removeQueryStringCallback(static fn ($value, $key) => str_contains($key, 'foo_'));
```
