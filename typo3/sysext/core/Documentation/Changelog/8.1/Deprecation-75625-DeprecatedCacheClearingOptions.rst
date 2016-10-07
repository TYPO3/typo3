
.. include:: ../../Includes.txt

=======================================================
Deprecation: #75625 - Deprecated cache clearing options
=======================================================

See :issue:`75625`

Description
===========

The following commands have been marked as deprecated and should not be used anymore:

* Method :php:`DataHandler->clear_cacheCmd()` with arguments `system` and `temp_cached`
* `userTSconfig` setting `options.clearCache.system`
* Option `$TYPO3_CONF_VARS['SYS']['clearCacheSystem']` has been removed


Impact
======

Directly or indirectly using method `clear_cacheCmd` with these arguments will trigger a deprecation log entry.


Affected Installations
======================

All installations with third party extensions using this method are affected.


Migration
=========

If the group of system caches needs to be deleted explicitly, use :php:`flushCachesInGroup('system')`
of `CacheManager` directly.

.. index:: PHP-API, LocalConfiguration