.. include:: /Includes.rst.txt

.. _deprecation-85122:

=======================================================
Deprecation: #85122 - Functionality in CharsetConverter
=======================================================

See :issue:`85122`

Description
===========

The following methods have been marked as deprecated due to better functionality mostly provided by native
PHP functionality, like :php:`mbstring` functions.

- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->synonyms`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->parse_charset()`
- Fourth parameter of :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->conv()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->convArray()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->utf8_to_entities()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->entities_to_utf8()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->crop()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->convCaseFirst()`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char2byte_pos()`

Additionally the following public properties have been changed to have a "protected" visibility,
as these only reflect internal state:

- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->noCharByteVal`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->parsedCharsets`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->toASCII`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->twoByteSets`
- :php-short:`\TYPO3\CMS\Core\Charset\CharsetConverter->eucBasedSets`


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
