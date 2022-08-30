# StringParser component

This component is designed to ease string handling.


## Usage

### Recursively replace simple tokens and insert tags ###

```php
// Output: This is the Contact page.
$this->stringParser->recursiveReplaceTokensAndTags('This is the ##page_title## page.', ['page_title' => 'Contact']);
```

### Convert a string to plain text using given options ###

```php
use Codefog\HasteBundle\StringParser;

// Output: This is the page.
$this->stringParser->convertToText('<p>This is the {{page::title}} page.</p>', StringParser::NO_TAGS & StringParser::NO_INSERTTAGS);
```

### Flatten an array to only string values ###

This is most useful for converting values to simple tokens. Simple tokens can only handle string values and have special
support for if/else constructs.

Example with an indexed array:

```php
$value = ['foo', 'bar'];
$data = [];

$this->stringParser->flatten($value, 'prefix', $data);

// Result
$data = [
    'prefix_foo' => '1',
    'prefix_bar' => '1',
    'prefix' => 'foo, bar',
];
```

Example with an associative array:

```php
$value = ['foo' => 'bar'];
$data = [];

$this->stringParser->flatten($value, 'prefix', $data);

// Result
$data = [
    'prefix_foo' => 'bar',
    'prefix' => '',
];
```
