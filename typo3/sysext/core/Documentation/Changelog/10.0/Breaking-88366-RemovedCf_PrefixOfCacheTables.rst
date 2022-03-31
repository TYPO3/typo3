.. include:: /Includes.rst.txt

=================================================
Breaking: #88366 - Removed prefix of cache tables
=================================================

See :issue:`88366`

Description
===========

In addition, when the Typo3DatabaseBackend now accesses and creates tables without the ``cf_``
prefix ("cf" = Caching Framework), so caches in the database are simply called `cache_rootline`
for instance.


Impact
======

Accessing the database tables directly with a ``cf_`` prefix will not work on the TYPO3 managed
cache tables.


Affected Installations
======================

Any TYPO3 instance using the Caching Framework with a Typo3DatabaseBackend.


Migration
=========

Use the Caching Framework directly.

In addition, run through the Database Table Analyzer of the Configuration module to
re-create any database tables of the Caching Framework.

.. index:: Database, NotScanned, ext:core
