.. include:: /Includes.rst.txt

=======================================================
Deprecation: #79316 - Deprecate ArrayUtility::inArray()
=======================================================

See :issue:`79316`

Description
===========

Deprecate ArrayUtility::inArray()


Impact
======

Calling :php:`ArrayUtility::inArray()` method will trigger a deprecation log entry. Code using this method will work until it is removed in TYPO3 v9.


Affected Installations
======================

Any installation using the mentioned method :php:`ArrayUtility::inArray()`.


Migration
=========

Use the native :php:`in_array()` function of PHP. It is strongly recommended to ensure the same type is used
everywhere and the 3rd parameter of :php:`in_array()` is set to :php:`true` to activate the type check.

.. index:: PHP-API
