..  include:: /Includes.rst.txt

..  _deprecation-111298-1793396636:

====================================================
Deprecation: #111298 - DataHandler->clear_cacheCmd()
====================================================

See :issue:`111298`

Description
===========

The method
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler->clear_cacheCmd()` has been
deprecated. Cache commands such as "pages", "all", a page id or
"cacheTag:<tag>" are flushed by
:php:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher`, which needs no
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` instance, see
:ref:`feature-111263-1793181135`.

Impact
======

Calling the method triggers a PHP :php:`E_USER_DEPRECATED` error. The
extension scanner reports usages.

Affected installations
======================

Installations with extensions calling
:php:`DataHandler->clear_cacheCmd()`, usually to flush caches without
processing any data map or command map.

Migration
=========

Flush the cache command with
:php-short:`\TYPO3\CMS\Core\Cache\Flush\CacheFlusher`. Pass a
:php-short:`\TYPO3\CMS\Core\Cache\Flush\CacheFlushingContext` created from
the backend user to keep checking the permissions of User TSconfig
:typoscript:`options.clearCache`. Without it, the command is flushed
unrestricted, which fits scheduler tasks or console commands.

Before:

..  code-block:: php

    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start([], []);
    $dataHandler->clear_cacheCmd('pages');

After:

..  code-block:: php

    use TYPO3\CMS\Core\Cache\Flush\CacheFlushDemand;
    use TYPO3\CMS\Core\Cache\Flush\CacheFlusher;
    use TYPO3\CMS\Core\Cache\Flush\CacheFlushingContext;

    public function __construct(
        private readonly CacheFlusher $cacheFlusher,
    ) {}

    public function flushPageCaches(): void
    {
        $this->cacheFlusher->flush(
            CacheFlushDemand::forCommand('pages'),
            CacheFlushingContext::fromBackendUser($GLOBALS['BE_USER'])
        );
    }

..  index:: PHP-API, FullyScanned, ext:core
