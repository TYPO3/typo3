
.. include:: ../../Includes.txt

===========================================================================================
Deprecation: #74156 - TemplateService::sortedKeyList and TemplateService->removeQueryString
===========================================================================================

See :issue:`74156`

Description
===========

The methods `TemplateService::sortedKeyList()` and `TemplateService->removeQueryString()` have been marked
as deprecated.


Impact
======

Calling one of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with a custom extension that uses these PHP methods.


Migration
=========

Use `ArrayUtility::filterAndSortByNumericKeys` and `rtrim($url, '?')` as drop-in replacements.

.. index:: PHP-API
