.. include:: /Includes.rst.txt

.. _deprecation-99019-1667905697:

================================================================
Deprecation: #99019 - Deprecated ext_emconf.php clearCacheOnLoad
================================================================

See :issue:`99019`

Description
===========

The array keys :php:`clearCacheOnLoad` and :php:`clearcacheonload`
in extension's :file:`ext_emconf.php` files have been deprecated and
can be removed.

When loading or unloading extensions using the extension manager,
all caches are always cleared.


Impact
======

When loading or unloading extensions using the extension manager, all
caches are flushed, regardless of the boolean toggle in :file:`ext_emconf.php`.


Affected installations
======================

Instances with extensions :file:`ext_emconf.php` files setting :php:`clearCacheOnLoad`
or :php:`clearcacheonload`.


Migration
=========

Simply drop this key from :file:`ext_emconf.php`. Extensions with this toggle set
to true that want to keep compatibility with both TYPO3 v11 and v12 should keep
the setting until v11 compatibility is dropped from the extensions.


.. index:: PHP-API, NotScanned, ext:extensionmanager
