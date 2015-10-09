# Haste InsertTags

Haste provides several insert tags for every-day use. 
They are not related to Haste functionality, but just for
general use in a Contao environment.


## Formatting helpers

### Apply insert tag flags to a string

The sole purpose of this insert tag is to pass a value to
the insert tag flags.

**Example:** `{{flag::foobar|strtoupper}}` will return `FOOBAR`.

See the [Contao documentation][flags] for available flags.


### Date / Time formatting

The `formatted_datetime` insert tag allows to format a timestamp
using either the internal date/time formatting settings or a custom
format.

##### Examples:

 1. `{{formatted_datetime::1234::d.m.Y}}`  
 	Formats the timestamp `1234` to Day.Month.Year.
 	Available formatting options can be found in the [PHP `date` method][date].
 	
 2. `{{formatted_datetime::1234::date}}`  
 	Formats the timestamp `1234` to the system's date format.
 	
 3. `{{formatted_datetime::1234::time}}`  
 	Formats the timestamp `1234` to the system's time format.
 	
 4. `{{formatted_datetime::1234::datim}}`  
 	Formats the timestamp `1234` to the system's date + time format.




### Get form generator option label from value

Sometimes it is necessary to get the label of a form generate option
(e.g. a radio button or checkbox field) from it's value.

**Example:** `{{options_label::1::value}}`

- First argument is the form field ID from form generator
- Second argument is the field value

If the field is not found or does not have a matching option,
the original value will be returned.

Particular use cases are in the Notification Center extension, where
simple tokens are available for input field values. By entering
`{{options_label::17::##form_fieldname##}}` into the notification text,
one can output the option label of field ID 17.


## DCA

### Label for a DCA field

`{{dca_label::tl_article::title}}` will output the label
for the DCA field `title` of table `tl_article`.

### Formatted value for a DCA field

`{{dca_value::tl_article::title::17}}` will output the formatted
`title` value of table `tl_article` record ID 17.

The insert tag is following the Contao's standards on value formatting.
As example, if the given field has a `foreignKey` attribute set,
the returned value will be looked up in the foreign table.

The following checks are performed:
 1. If DCA field has a `foreignKey`, lookup result in the foreign table.
 2. If value is an array, recursively resolve and implode to a comma-separated list.
 3. If DCA field input is a date (`rgxp => date`), format value as date string
 4. If DCA field input is a time (`rgxp => time`), format value as time
 5. If DCA field input is a date + time (`rgxp => datim`), format value as date + time string
 6. If DCA field is a single checkbox, return `yes` or `no` labels
 7. If DCA field allows HTML input, encode the string so the HTML code is visible
 8. If DCA field has `reference` set, try to find the value in the `reference` array
 9. If DCA field has `options`, try to find the value in the `options` array



## Other

### Generate random number

1. **Simple version:** `{{rand}}`  
    Generates a random number using PHP's `mt_rand` method.
	
2. **Extended version:** `{{rand::1::100}}`  
	If you pass two arguments to the insert tag, you can define
	the minimum and maximum value (see `mt_rand` documentation).
	

[flags]: https://docs.contao.org/books/manual/current/en/04-managing-content/insert-tags.html#insert-tag-flags
[date]: http://php.net/date
