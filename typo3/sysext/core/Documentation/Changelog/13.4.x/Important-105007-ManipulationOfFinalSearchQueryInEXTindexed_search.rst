..  include:: /Includes.rst.txt

..  _important-105007-1728977233:

=============================================================================
Important: #105007 - Manipulation of final search query in EXT:indexed_search
=============================================================================

See :issue:`105007`

Description
===========

By removing the :typoscript:`searchSkipExtendToSubpagesChecking` option in
:issue:`97530`, there might have been performance implications for installations
with a lot of sites. This could be circumvented by adjusting the search query
manually, using available hooks. Since those hooks have also been removed with
:issue:`102937`, developers were no longer be able to handle the query
behaviour.

Therefore, the PSR-14 :php:`BeforeFinalSearchQueryIsExecutedEvent` has been
introduced which allows developers to manipulate the :php:`QueryBuilder`
instance again, just before the query gets executed.

Additional context information, provided by the new event:

* :php:`searchWords` - The corresponding search words list
* :php:`freeIndexUid` - Pointer to which indexing configuration should be searched in. 
  -1 means no filtering. 0 means only regular indexed content.

.. important::

    The provided query (the :php:`QueryBuilder` instance) is controlled by
    TYPO3 and is not considered public API. Therefore, developers using this
    event need to keep track of underlying changes by TYPO3. Such changes might
    be further performance improvements to the query or changes to the
    database schema in general.

Example
=======

..  code-block:: php

    <?php
    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\IndexedSearch\Event\BeforeFinalSearchQueryIsExecutedEvent;

    final readonly class EventListener
    {
        #[AsEventListener(identifier: 'manipulate-search-query')]
        public function beforeFinalSearchQueryIsExecuted(BeforeFinalSearchQueryIsExecutedEvent $event): void
        {
            $event->queryBuilder->andWhere(
                $event->queryBuilder->expr()->eq('some_column', 'some_value')
            );
        }
    }

..  index:: PHP-API, ext:indexed_search
