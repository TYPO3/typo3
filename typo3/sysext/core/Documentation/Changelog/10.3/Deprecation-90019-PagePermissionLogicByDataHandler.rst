.. include:: /Includes.rst.txt

==========================================================
Deprecation: #90019 - Page permission logic by DataHandler
==========================================================

See :issue:`90019`

Description
===========

A new :php:`PagePermissionAssembler` class builds the page permissions, allowing to thin out certain parts of :php:`DataHandlers` responsibilities.

The following properties and methods within :php:`DataHandler` have been marked as deprecated:

* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->defaultPermissions`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->pMap`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->setTSconfigPermissions()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->assemblePermissions()`

The following methods should only be called with integers as permission argument:

* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->doesRecordExist()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->recordInfoWithPermissionCheck()`


Impact
======

Calling the mentioned methods will trigger a PHP :php:`E_USER_DEPRECATED` error and will be removed in TYPO3 v11.0.


Affected Installations
======================

Any TYPO3 installation that enriches page permission handling and directly accesses the methods or properties in :php:`DataHandler`.


Migration
=========

Ensure to use the new :php:`PagePermissionAssembler` PHP class
which serves as a proper API for creating page permissions.

.. index:: PHP-API, PartiallyScanned, ext:core
