.. include:: /Includes.rst.txt

==================================================
Deprecation: #86404 - $GLOBALS['TYPO3_LOADED_EXT']
==================================================

See :issue:`86404`

Description
===========

The global :php:`$GLOBALS['TYPO3_LOADED_EXT']` has been marked as deprecated in favor
of the :php:`PackageManager` API.


Impact
======

Accessing :php:`$GLOBALS['TYPO3_LOADED_EXT']` is discouraged.


Affected Installations
======================

Instances with extensions using :php:`$GLOBALS['TYPO3_LOADED_EXT']`.


Migration
=========

Use the :php:`getActivePackages()` method of
:php:`\TYPO3\CMS\Core\Package\PackageManager` to get a list of active
packages.

.. index:: PHP-API, FullyScanned
