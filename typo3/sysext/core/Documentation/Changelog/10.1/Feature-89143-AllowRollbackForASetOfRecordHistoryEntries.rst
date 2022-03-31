.. include:: /Includes.rst.txt

====================================================================
Feature: #89143 - Allow rollback for a set of record history entries
====================================================================

See :issue:`89143`

Description
===========

To allow rollbacks for a set of record history entries, it is now possible to add a correlationId
while creating the RecordHistory entry. The correlationId should be an UUID but could also be any
string which is useful to identify a set of entries.

To use this feature, an additional parameter :php:`$correlationId` has been added to the following methods of
:php:`\TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore`:

* :php:`addRecord(string $table, int $uid, array $payload, CorrelationId $correlationId = null)`
* :php:`modifyRecord(string $table, int $uid, array $payload, CorrelationId $correlationId = null)`
* :php:`deleteRecord(string $table, int $uid, CorrelationId $correlationId = null)`
* :php:`undeleteRecord(string $table, int $uid, CorrelationId $correlationId = null)`
* :php:`moveRecord(string $table, int $uid, array $payload, CorrelationId $correlationId = null)`

To resolve all entries for a given :php:`$correlationId` a new method has been added to the
:php:`\TYPO3\CMS\Core\DataHandling\History\RecordHistory` class:

* :php:`\TYPO3\CMS\Core\DataHandling\History\RecordHistory::findEventsForCorrelation(string $correlationId): array`


.. index:: Backend, PHP-API, ext:backend
