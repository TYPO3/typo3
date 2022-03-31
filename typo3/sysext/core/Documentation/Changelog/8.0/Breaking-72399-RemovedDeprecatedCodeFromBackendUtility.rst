
.. include:: /Includes.rst.txt

==============================================================
Breaking: #72399 - Removed deprecated code from BackendUtility
==============================================================

See :issue:`72399`

Description
===========

Remove deprecated code from BackendUtility

The following methods have been removed:

`getExcludeFields()`
`getExplicitAuthFieldValues()`
`getSystemLanguages()`
`getRegisteredFlexForms()`
`implodeTSParams()`
`getThumbNail()`
`helpTextIcon()`
`getUrlToken()`
`exec_foreign_table_where_query()`
`replaceMarkersInWhereClause()`
`RTEgetObj()`
`countVersionsOfRecordsOnPage()`
`getPathType_web_nonweb()`
`isTableMovePlaceholderAware()`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to one of the above mentioned methods.


Migration
=========

For `helpTextIcon()` use `cshItem()` instead.
For `isTableMovePlaceholderAware()` use `isTableWorkspaceEnabled()` directly.
For `countVersionsOfRecordsOnPage()` use `\TYPO3\CMS\Workspaces\Service\WorkspaceService::hasPageRecordVersions` to check for record versions.

.. index:: PHP-API, Backend
