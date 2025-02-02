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

Please refer to chapter `Implementing an event listener in your extension <https://docs.typo3.org/permalink/t3coreapi:eventdispatcherimplementation>`_
in TYPO3 explained on how to listen to these events.
