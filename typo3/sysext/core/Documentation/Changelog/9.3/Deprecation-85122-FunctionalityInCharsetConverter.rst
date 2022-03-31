.. include:: /Includes.rst.txt

=======================================================
Deprecation: #85122 - Functionality in CharsetConverter
=======================================================

See :issue:`85122`

Description
===========

The following methods have been marked as deprecated due to better functionality mostly provided by native
PHP functionality, like :php:`mbstring` functions.

- :php:`CharsetConverter->synonyms`
- :php:`CharsetConverter->parse_charset()`
- Fourth parameter of :php:`CharsetConverter->conv()`
- :php:`CharsetConverter->convArray()`
- :php:`CharsetConverter->utf8_to_entities()`
- :php:`CharsetConverter->entities_to_utf8()`
- :php:`CharsetConverter->crop()`
- :php:`CharsetConverter->convCaseFirst()`
- :php:`CharsetConverter->utf8_char2byte_pos()`

Additionally the following public properties have been changed to have a "protected" visibility,
as these only reflect internal state:

- :php:`CharsetConverter->noCharByteVal`
- :php:`CharsetConverter->parsedCharsets`
- :php:`CharsetConverter->toASCII`
- :php:`CharsetConverter->twoByteSets`
- :php:`CharsetConverter->eucBasedSets`


Impact
======

Calling any of the methods or accessing any of the properties will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions making use of the CharsetConverter methods or properties directly.


Migration
=========

Use native PHP equivalents instead, see the methods directly for substitutes.

.. index:: PHP-API, FullyScanned
