# API changes

## Version 4.* to 5.0

### Conversion to bundle

Haste becomes a regular Contao bundle as of version 5.0. 

### Removed components

1. `Haste\Dca\PaletteManipulator` – it is a part of Contao core now.
2. `Haste\Data` – obsolete stuff.
3. `Haste\DateTime` – use an alternative, for example [nesbot/carbon](https://github.com/briannesbitt/Carbon).
4. `Haste\Frontend` – obsolete stuff.
5. `Haste\Geodesy` – obsolete stuff, use an alternative.
6. `Haste\Generator` – obsolete stuff.
7. `Haste\Haste` – obsolete stuff.
8. `Haste\Http` – use Symfony components instead.
9. `Haste\Input` – auto_item is always active in Contao core now.
10. `Haste\Number` – obsolete stuff.
11. `Haste\Units` – use an alternative, for example [jordanbrauer/unit-converter](https://github.com/jordanbrauer/unit-converter).
12. `Haste\Util\Debug` – obsolete stuff.
13. `Haste\Util\RepositoryVersion` – obsolete stuff.
