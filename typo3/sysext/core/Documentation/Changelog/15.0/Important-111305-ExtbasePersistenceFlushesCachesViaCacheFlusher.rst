..  include:: /Includes.rst.txt

..  _important-111305-1793474510:

========================================================================
Important: #111305 - Extbase persistence flushes caches via CacheFlusher
========================================================================

See :issue:`111305`

Description
===========

Extbase persistence now flushes the caches of persisted records through
:php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher`, the same service
DataHandler uses, see :ref:`feature-111263-1793181135`. The internal class
:php:`\TYPO3\CMS\Extbase\Service\CacheService` has been removed. Code that
used it to flush page caches should use
:php-short:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher` with
:php:`CacheFlushDemand::forPageTagsByPageIds()` or
:php:`CacheFlushDemand::forTags()` instead.

The caches are flushed once at the end of every
:php:`PersistenceManager->persistAll()`, if the TypoScript setting
:typoscript:`config.tx_extbase.persistence.enableAutomaticCacheClearing` is
enabled, which is the default. Previously, the records were collected during
the request and flushed by the Extbase bootstrap after the plugin or module
had been rendered.

This changes how caches are flushed for records persisted by Extbase:

*   Caches are also flushed when :php:`persistAll()` is called outside of an
    Extbase plugin or module, for example in console commands or scheduler
    tasks. Without a request, the setting falls back to its default.
*   Page TSconfig is evaluated like for records saved in the backend:
    :typoscript:`TCEMAIN.clearCache_disable`,
    :typoscript:`TCEMAIN.clearCache_pageSiblingChildren` and
    :typoscript:`TCEMAIN.clearCache_pageGrandParent` are respected, and
    :typoscript:`TCEMAIN.clearCacheCmd` flushes page ids and
    :typoscript:`cacheTag:<tag>` commands. The commands "pages" and "all"
    are never executed for records persisted by Extbase.
*   A persisted page record flushes the caches of the page, its siblings and
    its parent page instead of the caches of its parent page only.
*   The PSR-14 events
    :php:`\TYPO3\CMS\Core\Cache\Event\BeforeRecordRelatedCachesAreFlushedEvent`,
    :php:`\TYPO3\CMS\Core\Cache\Event\BeforeCacheFlushEvent` and
    :php:`\TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent` are dispatched
    for records persisted by Extbase.

Records saved in the backend now also flush the cache tag
:php:`<table>_pid_<pid>`, which the automatic frontend cache tagging of
Extbase repositories (feature toggle :php:`frontend.cache.autoTagging`)
adds to lists of records of a storage page. A new record created in the
backend therefore evicts cached lists of its storage page as well.

A record moved to another page flushes the caches of its original page in
addition to the caches of its new page. This applies to records moved in
the backend and to objects whose "pid" is changed and persisted by Extbase,
which requires a TCA column "pid" of type "passthrough".

..  index:: Frontend, PHP-API, TypoScript, ext:extbase
