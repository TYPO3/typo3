.. include:: /Includes.rst.txt

.. _feature-93494:

==========================================================
Feature: #93494 - New PSR-14 ModifyQueryForLiveSearchEvent
==========================================================

See :issue:`93494`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Search\Event\ModifyQueryForLiveSearchEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Backend\Search\LiveSearch\LiveSearch` class
and allows extensions to modify the :php:`QueryBuilder` instance
before execution.

The event features the following methods:

- :php:`getQueryBuilder()`: Returns the current :php:`QueryBuilder` instance
- :php:`getTableName()`: Returns the table, for which the query will be executed

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\EventListener\ModifyQueryForLiveSearchEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/modify-query-for-live-search-event-listener'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Search\Event\ModifyQueryForLiveSearchEvent;

    final class ModifyQueryForLiveSearchEventListener
    {
        public function __invoke(ModifyQueryForLiveSearchEvent $event): void
        {
            // Get the current instance
            $queryBuilder = $event->getQueryBuilder();

            // Change limit depending on the table
            if ($event->getTableName() === 'pages') {
                $queryBuilder->setMaxResults(2);
            }

            // Reset the orderBy part
            $queryBuilder->resetQueryPart('orderBy');
        }
    }

Impact
======

It is now possible to use a new PSR-14 event for modifying the live
search query. This can be used, for example, to adjust the limit for a specific
table or to change the result order.

.. index:: Backend, PHP-API, ext:backend
