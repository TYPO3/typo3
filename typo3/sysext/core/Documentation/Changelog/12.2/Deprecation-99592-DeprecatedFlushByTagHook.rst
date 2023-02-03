.. include:: /Includes.rst.txt

.. _deprecation-99592-1674033859:

==================================================
Deprecation: #99592 - Deprecated "flushByTag" hook
==================================================

See :issue:`99592`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php']['flushByTag']`
has been marked as deprecated.

It is recommended to implement a custom cache
frontend using :ref:`Frontend API<t3coreapi:caching-frontend>` when custom cache
functionality is required.

Impact
======

Any hook implementation registered will not be executed anymore
in TYPO3 v13. The extension scanner will report possible usages.

Affected installations
======================

All installations making use of the deprecated hook.

Migration
=========

Migrate corresponding cache functionality in the :php:`flushByTag()` method of your
own cache frontend implementation.

.. index:: PHP-API, FullyScanned, ext:core
