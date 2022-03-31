.. include:: /Includes.rst.txt

===========================================================================
Deprecation: #80524 - PageRepository::getHash and PageRepository::storeHash
===========================================================================

See :issue:`80524`

Description
===========

The two static methods :php:`PageRepository::getHash()` and :php:`PageRepository::storeHash()`, that
act as simple wrappers for the Caching Frameworks's "cache_hash" frontend, have been deprecated.


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any installation with a custom installation using any of the methods.


Migration
=========

Use the Caching Framework directly. Simply spoken, the code that still exists in the functions,
can simply be copied into the third-party extensions' code.

.. index:: PHP-API, Frontend
