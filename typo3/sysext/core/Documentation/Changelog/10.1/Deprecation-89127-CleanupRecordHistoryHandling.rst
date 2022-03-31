.. include:: /Includes.rst.txt

====================================================
Deprecation: #89127 - Cleanup RecordHistory handling
====================================================

See :issue:`89127`


Description
===========

The following properties of the :php:`\TYPO3\CMS\Backend\History\RecordHistory` class have been marked as deprecated:

* :php:`changeLog`
* :php:`lastHistoryEntry`

The properties are now protected and have a public getter function.

The following public methods of the :php:`\TYPO3\CMS\Backend\History\RecordHistory` class have changed visibility from public to protected:

* :php:`getHistoryEntry()`
* :php:`getHistoryData()`

The following methods of the :php:`\TYPO3\CMS\Backend\History\RecordHistory` class have been marked as deprecated:

* :php:`createChangeLog()`, use :php:`getChangeLog()` instead
* :php:`shouldPerformRollback()`
* :php:`getElementData()`, use :php:`getElementInformation()` instead
* :php:`performRollback()`, use :php:`RecordHistoryRollback::performRollback()` instead
* :php:`createMultipleDiff()`, use :php:`getDiff()` instead
* :php:`setLastHistoryEntry()`, use :php:`setLastHistoryEntryNumber()` instead


Impact
======

Accessing these properties and methods directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions or TypoScript directly
accessing these properties and methods.


Migration
=========

Use the mentioned alternative methods and new classes.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
