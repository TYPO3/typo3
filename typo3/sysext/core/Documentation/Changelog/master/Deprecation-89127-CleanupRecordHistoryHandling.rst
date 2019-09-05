.. include:: ../../Includes.txt

====================================================
Deprecation: #89127 - Cleanup RecordHistory handling
====================================================

See :issue:`89127`


Description
===========

The following properties of the :php:`\TYPO3\CMS\Backend\History\RecordHistory` class have been marked as deprecated:

* changeLog
* lastHistoryEntry

The properties are now protected and have a public getter function.

The following public methods of the :php:`\TYPO3\CMS\Backend\History\RecordHistory` class have been marked as deprecated:

* getHistoryEntry()
* getHistoryData()

The methods have been set to protected and will not be callable anymore in TYPO3 v11.0.

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
accessing these values and methods.


Migration
=========

Use the mentioned alternative methods and new classes.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
