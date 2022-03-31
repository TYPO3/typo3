.. include:: /Includes.rst.txt

======================================================
Breaking: #82640 - Re-arranging global debug functions
======================================================

See :issue:`82640`

Description
===========

The global function :php:`xdebug()` has been dropped in favor of using :php:`debug()`.

The global function :php:`debug()` now only takes a maximum of three function arguments.


Impact
======

Calling `xdebug()` globally will throw in a PHP fatal error.


Affected Installations
======================

Any TYPO3 installation in a development environment using the global functions in an old-fashioned way.


Migration
=========

Instead of `xdebug()` simply use `debug()` as an alias. Ensure that when calling `debug()` that a
maximum of three arguments are handed over to the function.

.. index:: PHP-API, FullyScanned
