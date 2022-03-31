.. include:: /Includes.rst.txt

======================================================
Deprecation: #85760 - GeneralUtility::unQuoteFilenames
======================================================

See :issue:`85760`

Description
===========

The method :php:`GeneralUtility::unQuoteFilenames()` has been marked as deprecated and will be removed in TYPO3 v10.


Impact
======

Calling the mentioned method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Third party code which accesses the method.


Migration
=========

No migration available.

.. index:: PHP-API, FullyScanned
