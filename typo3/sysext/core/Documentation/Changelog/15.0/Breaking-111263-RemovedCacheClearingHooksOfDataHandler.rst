..  include:: /Includes.rst.txt

..  _breaking-111263-1793181136:

===============================================================
Breaking: #111263 - Removed cache clearing hooks of DataHandler
===============================================================

See :issue:`111263`

Description
===========

The cache flushing logic of :php:`\TYPO3\CMS\Core\DataHandling\DataHandler`
has been moved to :php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher`, see
:ref:`feature-111263-1793181135`. The following hooks have been removed in
favor of PSR-14 events:

*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']`

The protected methods :php:`prepareCacheFlush()` and
:php:`getOriginalParentOfRecord()` of
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` have been removed. Its
constructor receives :php-short:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher`
and :php-short:`\TYPO3\CMS\Core\Cache\Flush\CacheFlushLogger` instead of
:php:`\TYPO3\CMS\Core\Cache\CacheManager` and
:php:`\TYPO3\CMS\Core\Service\OpcodeCacheService`.

Flushing the "pages" cache group or all caches in the backend, for example
via the cache toolbar or Page TSconfig :typoscript:`TCEMAIN.clearCacheCmd`,
now dispatches :php:`\TYPO3\CMS\Core\Cache\Event\CacheFlushEvent`, like the
:bash:`cache:flush` command. Flushing all caches flushes every cache group,
including the default group of caches configured without :php:`groups`.
Caches configured with an empty list of groups, such as the runtime cache,
are flushed directly, as before.

Impact
======

Hook implementations registered under :php:`clearPageCacheEval` or
:php:`clearCachePostProc` are no longer executed.

Affected installations
======================

Installations with extensions registering one of the hooks are affected. The
extension scanner reports their usage as a strong match.

Migration
=========

Implementations of :php:`clearPageCacheEval`, and implementations of
:php:`clearCachePostProc` that changed the cache tags or commands of a record,
become listeners of
:php:`\TYPO3\CMS\Core\Cache\Event\BeforeRecordRelatedCachesAreFlushedEvent`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/FlushNewsListPage.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Cache\Event\BeforeRecordRelatedCachesAreFlushedEvent;

    final readonly class FlushNewsListPage
    {
        #[AsEventListener('my-extension/flush-news-list-page')]
        public function __invoke(BeforeRecordRelatedCachesAreFlushedEvent $event): void
        {
            if ($event->table === 'tx_news_domain_model_news') {
                $event->addPageId(42);
            }
        }
    }

Implementations that reacted to a cache command or flushed further caches
become listeners of :php:`\TYPO3\CMS\Core\Cache\Event\BeforeCacheFlushEvent`,
whose plan holds the requested records and commands and accepts further cache
tags and groups. Follow-up work such as purging a reverse proxy belongs into a
listener of :php:`\TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent`.

Implementations that read the data map, command map or history records of the
passed :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` should use the
DataHandler hooks :php:`processDatamap_afterAllOperations` and
:php:`processCmdmap_afterFinish` instead.

..  index:: Backend, PHP-API, FullyScanned, ext:core
