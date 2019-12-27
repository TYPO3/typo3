.. include:: ../../Includes.txt

=====================================================================
Deprecation: #90019 - Extraction of DataHandler page permission logic
=====================================================================

See :issue:`90019`

Description
===========

A new PagePermissionAssembler class builds the page permissions, allowing to thin out certain parts of DataHandlers responsibilities.

The following properties and methods within :php:`DataHandler` are now deprecated:
- :php:`TYPO3\CMS\Core\DataHandling\DataHandler->defaultPermissions`
- :php:`TYPO3\CMS\Core\DataHandling\DataHandler->pMap`
- :php:`TYPO3\CMS\Core\DataHandling\DataHandler->setTSconfigPermissions()`
- :php:`TYPO3\CMS\Core\DataHandling\DataHandler->assemblePermissions()`

The methods
- :php:`TYPO3\CMS\Core\DataHandling\DataHandler->doesRecordExist()`
- :php:`TYPO3\CMS\Core\DataHandling\DataHandler->recordInfoWithPermissionCheck()`

should only be called with integers as permission argument.


Impact
======

Calling the mentioned methods will trigger a PHP deprecation warning but will continue to work until TYPO3 v11.0.


Affected Installations
======================

Any TYPO3 installations that enrich page permission handling and directly access the methods or properties in DataHandler.


Migration
=========

Ensure to use the new :php:`PagePermissionAssembler` PHP class
which serves as a proper API for creating page records.

.. index:: PHP-API, PartiallyScanned, ext:core