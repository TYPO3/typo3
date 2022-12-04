.. include:: /Includes.rst.txt

.. _feature-99033-1668008969:

=====================================================
Feature: #99033 - Add table filter for backend search
=====================================================

See :issue:`99033`

Description
===========

The TYPO3 backend search (aka "Live Search") is using the
:php:`\TYPO3\CMS\Backend\Search\LiveSearch\DatabaseRecordProvider` to search
for records in database tables, having :php:`searchFields` configured in TCA.

In some individual cases, it may not be desired to search in a certain table.
Therefore, the new event :php:`\TYPO3\CMS\Backend\Search\Event\BeforeSearchInDatabaseRecordProviderEvent`
has been introduced, which allows to exclude / ignore such tables by adding them
to a deny list. Additionally, the new PSR-14 event can be used to further
limit the search result on certain page IDs or to modify the search query
altogether.

The event features the following methods:

- :php:`getSearchPageIds()`: Returns the page ids to search in
- :php:`setSearchPageIds()`: Allows to define page ids to search in
- :php:`getSearchDemand()`: Returns the :php:`SearchDemand`, used by the live search
- :php:`setSearchDemand()`: Allows to set a custom :php:`SearchDemand` object
- :php:`ignoreTable()`: Allows to ignore / exclude a table from the lookup
- :php:`setIgnoredTables()`: Allows to overwrite the ignored tables
- :php:`isTableIgnored()`: Returns whether a specific table is ignored
- :php:`getIgnoredTables()`: Returns all tables to be ignored from the lookup

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\EventListener\BeforeSearchInDatabaseRecordProviderEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/before-search-in-database-record-provider-event-listener'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Search\Event\BeforeSearchInDatabaseRecordProviderEvent;

    final class ModifyEditFileFormDataEventListener
    {
        public function __invoke(BeforeSearchInDatabaseRecordProviderEvent $event): void
        {
            $event->ignoreTable('my_custom_table');
        }
    }

Impact
======

It is now possible to ignore specific tables from the backend search using
the new PSR-14 event :php:`BeforeSearchInDatabaseRecordProviderEvent`. The event
also allows to adjust the page IDs to search in as well as to modify the
corresponding :php:`SearchDemand` object.

.. index:: Backend, PHP-API, ext:backend
