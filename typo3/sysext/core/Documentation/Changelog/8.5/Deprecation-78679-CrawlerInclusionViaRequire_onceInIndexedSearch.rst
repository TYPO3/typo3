.. include:: ../../Includes.txt

==========================================================================
Deprecation: #78679 - Crawler inclusion via require_once in Indexed Search
==========================================================================

See :issue:`78679`

Description
===========

The system extension "Indexed Search" has support for `EXT:crawler`, by using the crawler library
to index a page.

This functionality is done under the hood via the Indexer class, which does a manual PHP call on
"require_once" - code which is not necessary anymore, since the TYPO3 Core class loader is in place. The public
PHP method `TYPO3\CMS\IndexedSearch\Indexer->includeCrawlerClass()` is therefore marked as
deprecated.


Impact
======

Calling the method `TYPO3\CMS\IndexedSearch\Indexer->includeCrawlerClass()` will trigger a
deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with a custom indexer written in PHP, and Indexed Search and Crawler
installed, and the custom indexer using the method call above.


Migration
=========

Remove the function call, as TYPO3 includes the PHP class automatically.

.. index:: ext:indexed_search, PHP-API