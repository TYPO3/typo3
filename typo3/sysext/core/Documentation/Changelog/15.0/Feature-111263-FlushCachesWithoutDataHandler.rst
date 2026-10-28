..  include:: /Includes.rst.txt

..  _feature-111263-1793181135:

===================================================
Feature: #111263 - Flush caches without DataHandler
===================================================

See :issue:`111263`

Description
===========

The cache flushing logic of :php:`\TYPO3\CMS\Core\DataHandling\DataHandler`
has been extracted into the new service
:php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher`. It flushes the caches of
changed records, pages, cache tags and cache commands without a
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` or a backend user.

What to flush is described by a
:php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlushDemand`:

*   :php:`CacheFlushDemand::forRecord('tt_content', 12)` flushes the pages
    related to a record, following the Page TSconfig options
    :typoscript:`TCEMAIN.clearCache_disable`,
    :typoscript:`TCEMAIN.clearCache_pageSiblingChildren`,
    :typoscript:`TCEMAIN.clearCache_pageGrandParent` and
    :typoscript:`TCEMAIN.clearCacheCmd`, exactly like
    :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` does.
*   :php:`CacheFlushDemand::forPageTagsByPageIds(1, 2)` flushes the cache
    tags "pageId_1" and "pageId_2" only, without resolving related pages or
    Page TSconfig. :php:`CacheFlushDemand::forTags('news')` flushes cache
    tags.
*   :php:`CacheFlushDemand::forCommand('pages')` accepts the cache commands
    known from :typoscript:`TCEMAIN.clearCacheCmd`.

On whose behalf caches are flushed is described by
:php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlushingContext`: the workspace and
whether flushing the "pages" group or all caches is permitted.
:php:`CacheFlushingContext::fromBackendUser()` derives the permissions from
User TSconfig :typoscript:`options.clearCache.pages` and
:typoscript:`options.clearCache.all`. The default context is unrestricted.

:php:`CacheFlusher::flush()` returns a
:php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlushResult` with the flushed cache
tags and groups, the records, and what was skipped and why.
:php:`CacheFlusher::resolve()` returns the same information without flushing
anything. It dispatches the two "Before" events described below as well, so
their listeners must only change what is flushed and leave any follow-up
work to :php:`\TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent`.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/MyImportService.php

    use TYPO3\CMS\Core\Cache\Flush\CacheFlusher;
    use TYPO3\CMS\Core\Cache\Flush\CacheFlushDemand;

    final readonly class MyImportService
    {
        public function __construct(
            private CacheFlusher $cacheFlusher,
        ) {}

        public function import(int $newsUid): void
        {
            // ... write the record ...
            $result = $this->cacheFlusher->flush(
                CacheFlushDemand::forRecord('tx_news_domain_model_news', $newsUid)
            );
        }
    }

All events are dispatched by the service itself, so callers only describe
what to flush. Cache groups are flushed by dispatching the existing
:php:`\TYPO3\CMS\Core\Cache\Event\CacheFlushEvent`, like the
:bash:`cache:flush` command does, so listeners of that event now also react
to group flushes triggered in the backend. The "Clear cache" actions of the
backend toolbar and page tree use the new service as well.

Three new PSR-14 events replace the removed
:ref:`hooks <breaking-111263-1793181136>`:

:php:`\TYPO3\CMS\Core\Cache\Event\BeforeRecordRelatedCachesAreFlushedEvent`
    Dispatched for every changed record with the pages, cache tags and cache
    commands related to it, which listeners may change with
    :php:`addPageId()`, :php:`addTag()`, :php:`hasTag()` and the setters.

:php:`\TYPO3\CMS\Core\Cache\Event\BeforeCacheFlushEvent`
    Dispatched once per flush with the complete
    :php-short:`\TYPO3\CMS\Core\Cache\Flush\CacheFlushPlan`, which listeners
    may change.

:php:`\TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent`
    Dispatched once after the caches were flushed, with the
    :php-short:`\TYPO3\CMS\Core\Cache\Flush\CacheFlushResult`, for example to
    purge a reverse proxy by the flushed cache tags.

Impact
======

Caches of records can be flushed with the same rules as in
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` from any place and
without a backend user, and every flush reports what it flushed.

..  index:: Backend, PHP-API, ext:core
