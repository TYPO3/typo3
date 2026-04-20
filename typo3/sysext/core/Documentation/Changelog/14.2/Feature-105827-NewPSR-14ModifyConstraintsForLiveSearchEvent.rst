..  include:: /Includes.rst.txt

..  _feature-105827-1751912675:

=================================================================
Feature: #105827 - New PSR-14 ModifyConstraintsForLiveSearchEvent
=================================================================

See :issue:`105827`, :issue:`105833`, :issue:`93494`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Backend\Search\Event\ModifyConstraintsForLiveSearchEvent`
has been added to TYPO3 Core. This event is dispatched in the
:php-short:`TYPO3\CMS\Backend\Search\LiveSearch\LiveSearch` class and allows
extensions to modify the
:php-short:`TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression`
constraints collected in an array before execution.

This makes it possible to add additional constraints to the main query
constraints, combined with a logical `OR`. These constraints could
not previously be accessed by the existing event
:php-short:`TYPO3\CMS\Backend\Search\Event\ModifyQueryForLiveSearchEvent`.

The event provides the following methods:

*   :php:`getConstraints()`: Returns the current array of query constraints
    (composite expressions).
*   :php:`addConstraint()`: Adds a single constraint.
*   :php:`addConstraints()`: Adds multiple new constraints.
*   :php:`getTableName()`: Returns the table for which the query is executed
    (for example, `pages` or `tt_content`).
*   :php:`getSearchDemand()`: Returns the search demand.

..  hint::

    Constraints are intended to be added only. This ensures that
    security-related mandatory constraints added by Core or extensions cannot
    be negatively affected. For this reason, there is no way to remove a
    constraint after it has been added.

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
        public function __construct(
            private ConnectionPool $connectionPool,
        ) {}

        #[AsEventListener('my-package/livesearch-enhanced')]
        public function __invoke(
            ModifyConstraintsForLiveSearchEvent $event,
        ): void {
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

Core itself uses this event to allow searching for frontend URIs in the
backend page tree.

Impact
======

A new PSR-14 event is now available for adding constraints to the live search
query. These constraints are combined with a logical `OR`.

..  index:: Backend, PHP-API, ext:backend
