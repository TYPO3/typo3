.. include:: /Includes.rst.txt

================================================================================
Feature: #89150 - Add events before and after rollback of record history entries
================================================================================

See :issue:`89150`

Description
===========

Two new events have been introduced into record history that will be dispatched before and after a revert action.

* :php:`\TYPO3\CMS\Backend\History\Event\BeforeHistoryRollbackStartEvent` before the rollback starts
* :php:`\TYPO3\CMS\Backend\History\Event\AfterHistoryRollbackFinishedEvent` after the rollback finished

Both events resolve some information about the :php:`RecordHistory` item:

* :php:`getRecordHistoryRollback()` returns the :php:`\TYPO3\CMS\Backend\History\RecordHistoryRollback` object
* :php:`getRollbackFields()` returns a string with the rollback fields
* :php:`getDiff()` returns an array with the differences
* :php:`getBackendUserAuthentication()` returns a :php:`\TYPO3\CMS\Backend\BackendUserAuthentication` object, which is used for this rollback operation

Additionally the :php:`\TYPO3\CMS\Backend\History\Event\AfterHistoryRollbackFinishedEvent` gets the DataHandler input data:

* :php:`getDataHandlerInput()` returns an array with the :php:`DataHandler` instructions.

.. index:: Backend, PHP-API, ext:backend
