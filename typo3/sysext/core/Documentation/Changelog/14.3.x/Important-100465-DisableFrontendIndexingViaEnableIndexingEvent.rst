..  include:: /Includes.rst.txt

..  _important-100465-1790207767:

===============================================================================
Important: #100465 - Disable frontend indexing via EnableIndexingEvent listener
===============================================================================

See :issue:`100465`

Description
===========

The PSR-14 event
:php:`\TYPO3\CMS\IndexedSearch\Event\EnableIndexingEvent` can now also be used
to prevent the indexing of the current page during frontend rendering, for
example if a plugin shows search results based on GET parameters that should
not end up in the index.

The new method :php:`disableIndexing()` marks the current request as not to be
indexed. It takes precedence over :php:`enableIndexing()`, over
:php:`config.index_enable` and over the extension setting
:php:`disableFrontendIndexing`. The method :php:`isIndexingDisabled()` returns
whether any listener has vetoed the indexing.

This replaces setting :php:`index_enable = 0` on
:php:`$GLOBALS['TSFE']->config`, which is no longer available.

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\IndexedSearch\Event\EnableIndexingEvent;

    final class DisableIndexingForSearchResults
    {
        #[AsEventListener]
        public function __invoke(EnableIndexingEvent $event): void
        {
            if ($event->getRequest()->getQueryParams()['tx_myplugin'] ?? false) {
                $event->disableIndexing();
            }
        }
    }

..  index:: Frontend, PHP-API, ext:indexed_search, NotScanned
