.. include:: ../../Includes.txt

==================================================
Deprecation: #85687 - Deprecate RuntimeCacheWriter
==================================================

See :issue:`85687`

Description
===========

The RuntimeCacheWriter was introduced in TYPO3 9.3 and misused the TYPO3 Caching Framework to provide InMemoryLogging
for the (rework in progress) AdminPanel. Instead of having a generic LogWriter in the LoggingFramework this belongs
scope-wise to the admin panel and implementation wise separated from the CachingFramework.

The RuntimeCacheWriter has therefore been deprecated and the AdminPanel will use custom log writers on demand when
they will become necessary.


Impact
======

Calling RuntimeCacheWriter will throw a deprecation error.


Affected Installations
======================

Any installation using the RuntimeCacheWriter.


Migration
=========

Write your own CacheWriter (see `\TYPO3\CMS\Core\Log\Writer\WriterInterface`) or - if you need the exact same
functionality - copy the old RuntimeCacheWriter to your own extension scope and use it.

.. index:: PHP-API, FullyScanned, ext:adminpanel
