# Haste Weight

Various classes to handle weighable objects and convert to different units. You can either use the Weight object or implement one of the available interfaces.


## Supported weight units ##

The `Scale` class can measure and convert between several weight units.

<table>
<tr><th>Unit</th><th>Short name</td><th>Constant</th></tr>
<tr><td>Miligram</td><td>mg</td><td><code>Scale::UNIT_MILIGRAM</code></td></tr>
<tr><td>Gram</td><td>g</td><td><code>Scale::UNIT_GRAM</code></td></tr>
<tr><td>Kilogram</td><td>kg</td><td><code>Scale::UNIT_KILOGRAM</code></td></tr>
<tr><td>Metric ton</td><td>t</td><td><code>Scale::UNIT_METRICTON</code></td></tr>
<tr><td>Carat</td><td>c</td><td><code>Scale::UNIT_CARAT</code></td></tr>
<tr><td>Ounce</td><td>oz</td><td><code>Scale::UNIT_OUNCE</code></td></tr>
<tr><td>Pund</td><td>lb</td><td><code>Scale::UNIT_POUND</code></td></tr>
<tr><td>Stone</td><td>st</td><td><code>Scale::UNIT_STONE</code></td></tr>
<tr><td>Grain</td><td>grain</td><td><code>Scale::UNIT_GRAIN</code></td></tr>
</table>


## Examples ##

### Convert a weight ###

```php
<?php

echo \Haste\Weight\Scale::convertWeight(
	1,
	\Haste\Weight\Scale::UNIT_KILOGRAM,
	\Haste\Weight\Scale::UNIT_POUND
);
```
> Returns `2.20459`


### Measure weight of multiple object ###

```php
<?php

$objScale = new Scale();

// Add one kilogram
$objScale->add(new \Haste\Weight\Weight(1, \Haste\Weight\Scale::UNIT_KILOGRAM));

// Add 500 gram
$objScale->add(new \Haste\Weight\Weight(5000, \Haste\Weight\Scale::UNIT_MILIGRAM));

echo $objScale->amountIn(\Haste\Weight\Scale::UNIT_GRAM);
```
> Returns `1005`

Add some more to the scale:
```php
<?php

// Add some stones
$objScale->add(new \Haste\Weight\Weight(3, \Haste\Weight\Scale::UNIT_STONE));

echo $objScale->amountIn(\Haste\Weight\Scale::UNIT_POUND);
```
> Returns `2257.60776`
