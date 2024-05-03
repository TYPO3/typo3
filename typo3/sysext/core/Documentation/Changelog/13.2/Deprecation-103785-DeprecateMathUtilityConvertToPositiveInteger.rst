.. include:: /Includes.rst.txt

.. _deprecation-103785-1714720280:

========================================================================
Deprecation: #103785 - Deprecate MathUtility::convertToPositiveInteger()
========================================================================

See :issue:`103785`

Description
===========

TYPO3 has a method to ensure an integer is always positive:
:php:`MathUtility::convertToPositiveInteger()`. However, the method is rather
"heavy" as it internally calls :php:`MathUtility::forceIntegerInRange()` and
therefore misuses a clamp mechanism to convert the integer to a positive number.

Also, the method name doesn't reflect what it actually does. Negative numbers
are not converted to their positive counterpart, but are swapped with `0`. Due
to the naming issue and the fact that the method can be superseded by a simple
:php:`max()` call, the method is therefore deprecated.

Impact
======

Calling :php:`MathUtility::convertToPositiveInteger()` will trigger a PHP
deprecation warning.


Affected installations
======================

All installations using :php:`MathUtility::convertToPositiveInteger()` are
affected.


Migration
=========

To recover the original behavior of the deprecated method, its call can be
replaced with :php:`max(0, $number)`. To actually convert negative numbers to
their positive counterpart, call :php:`abs($number)`.

.. index:: PHP-API, FullyScanned, ext:core
