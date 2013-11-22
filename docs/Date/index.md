# Haste Date

Provides helper classes to handle dates.

## Examples ###

### Calculate age of a person ###

```php
<?php

$objMember = \MemberModel::findByPk('1');
$intAge = \Haste\DateTime\DateTime::createFromFormat('U', $objMember->dateOfBirth)->getAge();
```


### Get zodiac sign of a person ###

```php
<?php

$objMember = \MemberModel::findByPk('1');
$strSign = \Haste\Date\ZodiacSign::getLabelFromTimestamp($objMember->dateOfBirth);
```
