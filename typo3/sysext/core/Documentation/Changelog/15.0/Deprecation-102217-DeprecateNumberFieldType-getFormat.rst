..  include:: /Includes.rst.txt

..  _deprecation-102217-1789270881:

===============================================================
Deprecation: #102217 - Deprecate `NumberFieldType->getFormat()`
===============================================================

See :issue:`102217`

Description
===========

The method
:php:`\TYPO3\CMS\Core\Schema\Field\NumberFieldType->getFormat()` has been
marked as deprecated and will be removed in TYPO3 v16.0.

The method is part of the TcaSchema API for the TCA type `number`.


Impact
======

Calling :php-short:`\TYPO3\CMS\Core\Schema\Field\NumberFieldType->getFormat()` triggers a PHP
:php:`E_USER_DEPRECATED` error.


Affected installations
======================

All installations using the TcaSchema API for type `number` and the
method :php:`getFormat()`


Migration
=========

Use :php:`\TYPO3\CMS\Core\Schema\Field\NumberFieldType->getScale()` to check
the number of decimal digits.
If it returns :php:`0` it is of former format `integer`,
otherwise it is of former format `decimal`.

..  index:: PHP-API, NotScanned, ext:core
