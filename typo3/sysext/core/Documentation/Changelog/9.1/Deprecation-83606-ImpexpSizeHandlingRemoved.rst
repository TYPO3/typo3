.. include:: ../../Includes.txt

===================================================
Deprecation: #83606 - impexp: Size handling removed
===================================================

See :issue:`83606`


Description
===========

When exporting or importing structures via extension :php:`impexp`,
records and files wrote size information to export files and checked
these during import. This functionality has been removed.


Impact
======

This change has no impact on editors, on PHP level, two class
properties have been marked as deprecated:

* :php:`TYPO3\CMS\Impexp\Export->maxRecordSize`

* :php:`TYPO3\CMS\Impexp\Export->maxExportSize`


Affected Installations
======================

Using these properties in PHP has been deprecated, they will be removed
with CMS 10. The extension scanner will find possible usages.


Migration
=========

Don't access these properties anymore, they are ignored.

.. index:: Backend, PHP-API, FullyScanned, ext:impexp
