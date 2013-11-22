# Haste Mass Units

Various classes to handle weighable objects and convert between different mass units. You can either use the Weight object or implement one of the available interfaces.


## Supported mass units ##

The `Unit` class can measure and convert between several mass units.

<table>
<tr><th>Unit</th><th>Short name</td><th>Constant</th></tr>
<tr><td>Miligram</td><td>mg</td><td><code>Unit::MILIGRAM</code></td></tr>
<tr><td>Gram</td><td>g</td><td><code>Unit::GRAM</code></td></tr>
<tr><td>Kilogram</td><td>kg</td><td><code>Unit::KILOGRAM</code></td></tr>
<tr><td>Metric ton</td><td>t</td><td><code>Unit::METRICTON</code></td></tr>
<tr><td>Carat</td><td>c</td><td><code>Unit::CARAT</code></td></tr>
<tr><td>Ounce</td><td>oz</td><td><code>Unit::OUNCE</code></td></tr>
<tr><td>Pund</td><td>lb</td><td><code>Unit::POUND</code></td></tr>
<tr><td>Stone</td><td>st</td><td><code>Unit::STONE</code></td></tr>
<tr><td>Grain</td><td>grain</td><td><code>Unit::GRAIN</code></td></tr>
</table>


## Examples ##

### Convert a weight ###

```php
<?php

echo \Haste\Units\Mass\Unit::convert(
	1,
	\Haste\Units\Mass\Unit::KILOGRAM,
	\Haste\Units\Mass\Unit::POUND
);
```
> Returns `2.20459`


### Measure weight of multiple object ###

```php
<?php

$objScale = new Scale();

// Add one kilogram
$objScale->add(new \Haste\Units\Mass\Weight(1, \Haste\Units\Mass\Unit::KILOGRAM));

// Add 500 gram
$objScale->add(new \Haste\Units\Mass\Weight(5000, \Haste\Units\Mass\Unit::MILIGRAM));

echo $objScale->amountIn(\Haste\Units\Mass\Unit::GRAM);
```
> Returns `1005`

Add some more to the scale:
```php
<?php

// Add some stones
$objScale->add(new \Haste\Units\Mass\Weight(3, \Haste\Units\Mass\Unit::STONE));

echo $objScale->amountIn(\Haste\Units\Mass\Unit::POUND);
```
> Returns `2257.60776`
