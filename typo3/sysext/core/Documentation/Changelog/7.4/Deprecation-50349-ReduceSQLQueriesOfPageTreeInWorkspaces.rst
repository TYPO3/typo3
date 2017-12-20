
.. include:: ../../Includes.txt

===================================================================
Deprecation: #50349 - Reduce SQL queries of page tree in workspaces
===================================================================

See :issue:`50349`

Description
===========

The performance of the calculation of versions of a record has been improved. Therefore the method
`\TYPO3\CMS\Backend\Utility::countVersionsOfRecordsOnPage()` has been marked as deprecated and is being replaced with
`\TYPO3\CMS\Workspaces\Service\WorkspaceService::hasPageRecordVersions()`.


Impact
======

All calls to the PHP method will throw a deprecation warning.


Affected Installations
======================

Instances which make use of `\TYPO3\CMS\Backend\Utility::countVersionsOfRecordsOnPage()`


Migration
=========

Use `\TYPO3\CMS\Workspaces\Service\WorkspaceService::hasPageRecordVersions()` instead.


.. index:: PHP-API, Backend, ext:workspaces
