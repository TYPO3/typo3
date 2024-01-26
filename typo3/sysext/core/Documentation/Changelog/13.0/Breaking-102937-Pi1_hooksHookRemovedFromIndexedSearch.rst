.. include:: /Includes.rst.txt

.. _breaking-102937-1706261180:

================================================================
Breaking: #102937 - `pi1_hooks` hook removed from Indexed Search
================================================================

See :issue:`102937`

Description
===========

Indexed Search provided the possibility to manipulate the search behavior via
hooks with :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks']`.

There are no public extensions supporting TYPO3 v12 using this hooking mechanism,
therefore it has been removed without replacement. In case there are private consumers
of these hooks, we will allow to add a dedicated event at appropriate places later.


Impact
======

If implemented, hooks in :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks']`
are not called anymore.


Affected installations
======================

All extensions using this hook are affected.


Migration
=========

No migration available.

.. index:: PHP-API, FullyScanned, ext:indexed_search
