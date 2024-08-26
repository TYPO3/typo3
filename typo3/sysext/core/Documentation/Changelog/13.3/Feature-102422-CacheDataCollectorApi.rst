.. include:: /Includes.rst.txt

.. _feature-109999-1700506000:

==================================================================
Feature: #102422 - Introduce CacheDataCollector Api
==================================================================

See :issue:`102422`

Description
===========

A new API has been introduced to collect cache tags and their corresponding
lifetime. This API is used in TYPO3 to accumulate cache tags from page cache and
content object cache.

The API is implemented as a new PSR-7 request attribute
:php:`'frontend.cache.collector'`, which makes this API independent from TSFE.

Every cache tag has a lifetime. The minimum lifetime is calculated
from all given cache tags. API users don't have to deal with it individually.
The default lifetime for a cache tag is 86400 seconds (24 hours).

The current TSFE API is deprecated in favor of the new API, as the
current cache tag API implementation does not allow to set lifetime and
extension authors had to work around it.

Example
-------

..  code-block:: php
    :caption: Add a single cache tag

    use TYPO3\CMS\Core\Cache\CacheTag;

    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
    $cacheDataCollector->addCacheTags(
        new CacheTag('tx_myextension_mytable')
    );

..  code-block:: php
    :caption: Add multiple cache tags with different lifetimes

    use TYPO3\CMS\Core\Cache\CacheTag;

    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
    $cacheDataCollector->addCacheTags(
        new CacheTag('tx_myextension_mytable_123', 3600),
        new CacheTag('tx_myextension_mytable_456', 2592000)
    );

..  code-block:: php
    :caption: Remove a cache tag

    use TYPO3\CMS\Core\Cache\CacheTag;

    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
    $cacheDataCollector->removeCacheTags(
        new CacheTag('tx_myextension_mytable_123')
    );

..  code-block:: php
    :caption: Remove multiple cache tags

    use TYPO3\CMS\Core\Cache\CacheTag;

    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
    $cacheDataCollector->removeCacheTags(
        new CacheTag('tx_myextension_mytable_123'),
        new CacheTag('tx_myextension_mytable_456')
    );

..  code-block:: php
    :caption: Get minimum lifetime, calculated from all cache tags

    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
    $cacheDataCollector->getLifetime();

..  code-block:: php
    :caption: Get all cache tags

    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
    $cacheDataCollector->getCacheTags();

The following event should only be used in code that has no access to the
request attribute :php:`'frontend.cache.collector'`, it is marked :php:`@internal`
and may vanish: It designed to allow passive cache-data signaling, without
exactly knowing the current context and not having the current request at hand.
It is not meant to allow for cache tag interception or extension.

..  code-block:: php
    :caption: Add cache tag without access to the request object

    $this->eventDispatcher->dispatch(
        new AddCacheTagEvent(
            new CacheTag('tx_myextension_mytable_123', 3600)
        )
    );

.. index:: PHP-API, ext:core
