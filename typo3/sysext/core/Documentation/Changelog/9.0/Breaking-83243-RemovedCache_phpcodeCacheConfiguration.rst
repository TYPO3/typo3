.. include:: ../../Includes.txt

============================================================
Breaking: #83243 - Removed cache_phpcode cache configuration
============================================================

See :issue:`83243`

Description
===========

The Caching Framework configuration for `cache_phpcode` is unused since
TYPO3 6.0 and has been removed without substitution.

Impact
======

Using `cache_phpcode` will throw a `NoSuchCacheException`.


Affected Installations
======================

Every installation using a 3rd party extension that still relies on `cache_phpcode` is affected.

.. index:: PHP-API, NotScanned, LocalConfiguration