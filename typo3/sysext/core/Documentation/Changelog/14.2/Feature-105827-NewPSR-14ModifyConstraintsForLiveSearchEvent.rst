.. include:: /Includes.rst.txt

.. _feature-105827-1751912675:

=================================================================
Feature: #105827 - New PSR-14 ModifyConstraintsForLiveSearchEvent
=================================================================

See :issue:`105827`, :issue:`105833`, :issue:`93494`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Search\Event\ModifyConstraintsForLiveSearchEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Backend\Search\LiveSearch\LiveSearch` class
and allows extensions to modify the :php:`CompositeExpression` constraints
gathered in an array, before execution. This allows to add or remove additional constraints to the main query
constraints that are combined in an logical `OR` conjunction, and could not be accessed
with the existing event :php:`\TYPO3\CMS\Backend\Search\Event\ModifyQueryForLiveSearchEvent`.

The event features the following methods:

- :php:`getConstraints()`: Returns the current array of query constraints (composite expression).
- :php:`addConstraint()`: Adds a single constraint.
- :php:`addConstraints()`: Adds multiple new constraints in one go.
- :php:`getTableName()`: Returns the table, for which the query will be executed (e.g. "pages"
  or "tt_content").
- :php:`getSearchDemand()`: Returns the search demand that is getting searched for

..  hint::

    Note that constraints are only intended to be added to the event, to not
    negatively impact security-related mandatory constraints added by Core or extensions.
    So there is no way to remove a constraint after it has been added.

Example
=======

The corresponding event listener class:

..  code-block:: php

    <?php

    namespace Vendor\MyPackage\Backend\EventListener;

    use TYPO3\CMS\Backend\Search\Event\ModifyConstraintsForLiveSearchEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Database\ConnectionPool;

    final readonly class PageRecordProviderEnhancedSearch
    {
        public function __construct(private ConnectionPool $connectionPool) {}

        #[AsEventListener('my-package/livesearch-enhanced')]
        public function __invoke(ModifyConstraintsForLiveSearchEvent $event): void
        {
            if ($event->getTableName() !== 'pages') {
                return;
            }

            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            // Add a constraint so that pages marked with "show_in_all_results=1"
            // will always be shown.
            $constraints[] = $queryBuilder->expr()->eq(
                'show_in_all_results',
                1,
            );

            $event->addConstraints(...$constraints);
        }
    }

The Core itself makes uses of this event to allow searching for frontend URIs
inside the backend tree.

Impact
======

It is now possible to use a new PSR-14 event for adding constraints to the Live
Search query, which are OR-combined.

.. index:: Backend, PHP-API, ext:backend
