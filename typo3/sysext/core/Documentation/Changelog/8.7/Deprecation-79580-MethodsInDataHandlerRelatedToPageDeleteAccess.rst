.. include:: ../../Includes.txt

====================================================================================
Deprecation: #79580 - Deprecate methods in DataHandler related to page delete access
====================================================================================

See :issue:`79580`

Description
===========

The following methods have been marked as deprecated:

* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->rmComma()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->noRecordsFromUnallowedTables()`

Impact
======

Calling these methods will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 extension calling one of these methods.


Migration
=========

Use native :php:`rtrim($input, ',')` instead of :php:`TYPO3\CMS\Core\DataHandling\DataHandler->rmComma()`.
No migration available for :php:`TYPO3\CMS\Core\DataHandling\DataHandler->noRecordsFromUnallowedTables()`.

.. index:: Database, PHP-API, Backend
