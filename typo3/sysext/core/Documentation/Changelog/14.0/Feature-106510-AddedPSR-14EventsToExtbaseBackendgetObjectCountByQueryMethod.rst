..  include:: /Includes.rst.txt

..  _feature-106510-1743800469:

=======================================================================================
Feature: #106510 - Added PSR-14 events to Extbase Backend::getObjectCountByQuery method
=======================================================================================

See :issue:`106510`

Description
===========

The class :php:`TYPO3\CMS\Extbase\Persistence\Generic\Backend` is the covering entity
to retrieve data from the database within the Extbase persistence framework.

Already in 2013 the :php:`getObjectDataByQuery` method got equipped with signals
(later migrated to events) in order to modify data retrieval.

Especially when used in combination with Extbase's :php:`QueryResult` there is also
a second important method :php:`getObjectCountByQuery`, which is often used in combination
with Fluid.

Extensions, or any other code using the existing events for data retrieval, have not been able
to consistently modify queries, such that results returned by :php:`QueryResult` were consistent.

The :php:`getObjectCountByQuery` method is now enhanced with events as well. This finally
allows extensions to modify all parts of query usage within Extbase's generic :php:`Backend`
to achieve consistent results.

The new events are:

- :php:`TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectCountEvent`
  may be used to modify the query before being passed on to the actual storage backend.
- :php:`TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectCountEvent`
  may be used to adjust the result.

Typically, an extension will want to implement events pair-wise:
:php:`ModifyQueryBeforeFetchingObjectCountEvent` together with
:php:`ModifyQueryBeforeFetchingObjectDataEvent`, and
:php:`ModifyResultAfterFetchingObjectCountEvent` together with
:php:`ModifyResultAfterFetchingObjectDataEvent`

..  index:: PHP-API, ext:extbase
