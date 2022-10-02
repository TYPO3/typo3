.. include:: /Includes.rst.txt

.. _breaking-96879:

===================================================
Breaking: #96879 - Hook "get_cache_timeout" removed
===================================================

See :issue:`96879`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['get_cache_timeout']`
used in TYPO3 Frontend for changing the cache timeout of a page stored in the
TYPO3 "pages" cache has been removed.

Impact
======

If an extension has registered a hook in :file:`ext_localconf.php` it will not
be executed anymore in TYPO3 v12 or later.

Affected Installations
======================

TYPO3 installations using this hook in custom extensions.

Migration
=========

Use the newly introduced PSR-14 event :ref:`ModifyCacheLifetimeForPageEvent <feature-96879-1663513042>`
and register a custom event listener.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
