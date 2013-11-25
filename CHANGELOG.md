HasteForm Changelog
===================

Version 3.0.1 (2013-11-25)
------------------------------

### Fixed
- Fixed the problem with deleting files in DC_Folder (see #37)

Version 3.0.0 (2013-11-22)
------------------------------

### General
- Added miscellaneous classes and rewritten a huge part of Haste

Version 2.0.2 (2013-10-10)
------------------------------

### Fixed
- Fixed possible error on foreach

Version 2.0.1 (2013-09-30)
------------------------------

### Fixed
- Added missing parent::__construct() call which prevented Contao from loading configuration objects
- Fixed adding fields from DCA not working because of reference
- Fixed missing key in widget arrays
- Fixed missing autoload.ini (#32)

Version 2.0.0 (2013-09-16)
------------------------------

### General
- This is a complete rewrite of HasteForm and thus incompatible with its previous versions. In addition to that, it is also only compatible with Contao versions 3.1+.

Version 1.0.2 (2013-04-09)
------------------------------

### Fixed
- The field value was set incorrectly (see #16)
- Fixed "hasErrors" was true even if the form was not submitted

Version 1.0.1 (2012-12-14)
------------------------------

### General
- Introduced Git Flow

### Fixed
- GET params were transformed on __get() instead of __set()
- Action form parameter was not correctly set
- Made the fields tableless by default

Version 1.0.0 (2012-10-05)
------------------------------

- Initial version