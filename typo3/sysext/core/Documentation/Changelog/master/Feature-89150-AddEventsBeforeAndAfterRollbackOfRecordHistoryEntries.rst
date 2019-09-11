.. include:: ../../Includes.txt

================================================================================
Feature: #89150 - Add events before and after rollback of record history entries
================================================================================

See :issue:`89150`

Description
===========

To interact before and after a record history entry is reverted two new events are dispatched:

* :php:`\TYPO3\CMS\Backend\History\Event\BeforeHistoryRollbackStartEvent` before the rollback starts
* :php:`\TYPO3\CMS\Backend\History\Event\AfterHistoryRollbackFinishedEvent` after the rollback finished

Both events resolves some information about the RecordHistory item:

* :php:`getRecordHistoryRollback()` returns the :php:`RecordHistoryRollback` object
* :php:`getRollbackFields()` returns a string with the rollback fields
* :php:`getDiff()` returns an array with the differences
* :php:`getBackendUserAuthentication()` returns a :php:`BackendUserAuthentication` object, which is used for this rollback operation

Additionally the :php:`\TYPO3\CMS\Backend\History\Event\AfterHistoryRollbackFinishedEvent` get the DataHandler input data:

* :php:`getDataHandlerInput()` returns an array with the :php:`DataHandler` instructions.

.. index:: Backend, PHP-API, ext:backend
