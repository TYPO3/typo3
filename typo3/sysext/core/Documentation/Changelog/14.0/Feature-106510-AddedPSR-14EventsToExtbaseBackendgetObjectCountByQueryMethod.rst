..  include:: /Includes.rst.txt

..  _feature-106510-1743800469:

=====================================================================================
Feature: #106510 - Add PSR-14 events to Extbase Backend::getObjectCountByQuery method
=====================================================================================

See :issue:`106510`

Description
===========

The class :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Backend` is the central
entity for retrieving data from the database within the Extbase persistence
framework.

Since 2013, the :php:`getObjectDataByQuery()` method has supported events
(previously signals) to allow modification of data retrieval.

In many use cases, especially when used together with
:php-short:`\TYPO3\CMS\Extbase\Persistence\QueryResult`, another key method is
involved: :php:`getObjectCountByQuery()`. This method is frequently used in
combination with Fluid templates.

Until now, extensions or other code using the existing events for data retrieval
could not ensure consistent modification of queries between data retrieval and
counting operations, resulting in mismatched query results.

The :php:`getObjectCountByQuery()` method has now been enhanced with new PSR-14
events, enabling extensions to modify all aspects of query processing within
Extbase's generic :php:`Backend` to achieve consistent results.

The new events are:

* :php-short:`\TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectCountEvent`
    Allows modification of the query before it is passed to the storage backend.
* :php-short:`\TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectCountEvent`
    Allows adjustment of the result after the query has been executed.

Typically, an extension should implement these events pairwise:

* :php:`ModifyQueryBeforeFetchingObjectCountEvent` together with
  :php:`ModifyQueryBeforeFetchingObjectDataEvent`
* :php:`ModifyResultAfterFetchingObjectCountEvent` together with
  :php:`ModifyResultAfterFetchingObjectDataEvent`

..  index:: PHP-API, ext:extbase
