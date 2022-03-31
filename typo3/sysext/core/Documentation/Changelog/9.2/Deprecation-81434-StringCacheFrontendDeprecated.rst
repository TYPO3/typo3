.. include:: /Includes.rst.txt

======================================================
Deprecation: #81434 - String Cache Frontend Deprecated
======================================================

See :issue:`81434`

Description
===========

The `StringFrontend` cache frontend has been marked as deprecated in favor of `VariableFrontend`.


Impact
======

Using `TYPO3\CMS\Core\Cache\Frontend\StringFrontend` will trigger a deprecation warning.


Affected Installations
======================

Any TYPO3 installation which defines any custom cache using `StringFrontend`.


Migration
=========

Replace `TYPO3\CMS\Core\Cache\Frontend\StringFrontend` occurrences in cache configurations with `TYPO3\CMS\Core\Cache\Frontend\VariableFrontend`.

.. index:: PHP-API, NotScanned
