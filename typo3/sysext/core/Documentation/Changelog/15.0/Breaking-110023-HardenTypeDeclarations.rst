.. include:: /Includes.rst.txt

.. _breaking-110023-1780947379:

============================================
Breaking: #110023 - Harden type declarations
============================================

See :issue:`110023`

Description
===========

The following PHP methods now use strict type declarations
instead of loose type hints (PHPdoc annotations).

This is considered a breaking change in case consumers are
not adjusted for strict types.

- :php:`\TYPO3\CMS\Backend\Utility::daysUntil` - Parameter `$tstamp` can now only be of type `integer` or `DateTimeInterface` - cast to `(int)` if strings were passed to this argument before.

Impact
======

Using the mentioned methods with wrong types will now result in a PHP exception, fatal error or warning,
depending on configured error reporting.

Migration
=========

Ensure proper PHP type declarations are used when calling these methods.

.. index:: PHP-API, NotScanned, Backend
