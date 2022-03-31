.. include:: /Includes.rst.txt

===================================================================
Deprecation: #88366 - Default caching framework cache names changed
===================================================================

See :issue:`88366`

Description
===========

TYPO3's internal Caching Framework has several caches already shipped with TYPO3 Core.

The caches have been renamed for convenience and for newcomers to overcome another "speciality"
of TYPO3 which was due to legacy and integration reasons back in TYPO3 4.3 when the Caching
Framework was introduced.

The following caches have been renamed:

* `cache_core` => `core`
* `cache_hash` => `hash`
* `cache_pages` => `pages`
* `cache_pagesection` => `pagesection`
* `cache_runtime` => `runtime`
* `cache_rootline` => `rootline`
* `cache_imagesizes` => `imagesizes`

The caches should now be accessed via :php:`$cacheManager->getCache('core')` instead of
:php:`$cacheManager->getCache('cache_core')` - without the ``cache_`` prefix.

In addition, when the DatabaseBackend cache is used, the database tables do not have the :sql:`cf_`
prefix anymore, making it clearer for integrators and developers what the caches mean.


Impact
======

When accessing the cache with a "cache" prefix, a PHP :php:`E_USER_DEPRECATED` error is triggered.


Affected Installations
======================

Any TYPO3 extension using the caching framework with the ``cache_`` prefix.


Migration
=========

Remove the ``cache_`` prefix from the callers code.

In addition, run through the Database Table Analyzer of the Configuration module to
re-create any database tables of the Caching Framework.

.. index:: Database, LocalConfiguration, NotScanned, ext:core
