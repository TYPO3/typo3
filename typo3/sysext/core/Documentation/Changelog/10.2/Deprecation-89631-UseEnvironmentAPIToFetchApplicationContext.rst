.. include:: /Includes.rst.txt

======================================================================
Deprecation: #89631 - Use Environment API to fetch application context
======================================================================

See :issue:`89631`

Description
===========

The Environment API, introduced in TYPO3 v9.3, allows access to the current Application Context (Production, Testing or Development).

The method :php:`GeneralUtility::getApplicationContext()` has been deprecated, as the same information is now available in :php:`TYPO3\CMS\Core\Core\Environment::getContext()`.


Impact
======

Calling the GeneralUtility method will trigger a PHP deprecation warning.


Affected Installations
======================

Any TYPO3 installation with a third-party extension calling the method directly.


Migration
=========

Use the Environment API call and substitute the method directly.

.. index:: PHP-API, FullyScanned, ext:core
