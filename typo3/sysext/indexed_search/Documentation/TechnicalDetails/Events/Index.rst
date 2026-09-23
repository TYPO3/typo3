:navigation-title: Events

..  include:: /Includes.rst.txt
..  _events:

================================================
PSR 14 events in system extension indexed search
================================================

The system extension :composer:`typo3/cms-indexed-search` features the following
PSR 14 events:

*   `BeforeFinalSearchQueryIsExecutedEvent <https://docs.typo3.org/permalink/t3coreapi:beforefinalsearchqueryisexecutedevent>`_
*   :php:`TYPO3\CMS\IndexedSearch\Event\EnableIndexingEvent`

    Dispatched during frontend rendering to decide whether the current page is
    indexed. Listeners can force indexing with :php:`enableIndexing()` even if
    frontend indexing is disabled, or prevent it with :php:`disableIndexing()`,
    which takes precedence.

*   :php:`TYPO3\CMS\IndexedSearch\Event\AfterSearchResultSetsAreGeneratedEvent`

    Dispatched after all result sets have been built in
    :php:`TYPO3\CMS\IndexedSearch\Controller\SearchController`.
    Allows modifying complete result sets, including pagination, rows, section
    data, and category metadata.

Please refer to chapter `Implementing an event listener in your extension <https://docs.typo3.org/permalink/t3coreapi:eventdispatcherimplementation>`_
in TYPO3 explained on how to listen to these events.
