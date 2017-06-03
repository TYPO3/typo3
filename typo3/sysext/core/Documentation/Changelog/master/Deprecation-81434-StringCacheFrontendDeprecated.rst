.. include:: ../../Includes.txt

======================================================
Deprecation: #81434 - String Cache Frontend Deprecated
======================================================

See :issue:`81434`

Description
===========

The ``StringFrontend`` cache frontend has been deprecated in favor of VariableFrontend.


Impact
======

The ``TYPO3\CMS\Core\Cache\Frontend\StringFrontend`` class is deprecated.


Affected Installations
======================

Any TYPO3 installation which defines any custom cache using ``StringFrontend``.


Migration
=========

Replace ``TYPO3\CMS\Core\Cache\Frontend\StringFrontend`` occurrences in cache configurations with ``TYPO3\CMS\Core\Cache\Frontend\VariableFrontend``.

.. index:: PHP-API, NotScanned