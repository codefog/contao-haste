# API changes

## Version 4.* to 5.0

### Conversion to bundle

Haste becomes a regular Contao bundle as of version 5.0. 

### Removed components

1. `Haste\Dca\PaletteManipulator` – it is a part of Contao core now.
2. `Haste\Dca\SortingFlag` – it is a part of Contao core now.
3. `Haste\Dca\SortingMode` – it is a part of Contao core now.
4. `Haste\Data` – obsolete stuff.
5. `Haste\DateTime` – use an alternative, for example [nesbot/carbon](https://github.com/briannesbitt/Carbon).
6. `Haste\Frontend` – obsolete stuff.
7. `Haste\Geodesy` – obsolete stuff, use an alternative.
8. `Haste\Generator` – obsolete stuff.
9. `Haste\Haste` – obsolete stuff.
10. `Haste\Http` – use Symfony components instead.
11. `Haste\Input` – auto_item is always active in Contao core now.
12. `Haste\Number` – obsolete stuff.
13. `Haste\Units` – use an alternative, for example [jordanbrauer/unit-converter](https://github.com/jordanbrauer/unit-converter).
14. `Haste\Util\Debug` – obsolete stuff.
15. `Haste\Util\RepositoryVersion` – obsolete stuff.
