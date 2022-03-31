
.. include:: /Includes.rst.txt

=====================================================
Deprecation: #70514 - dynamicConfigFile is deprecated
=====================================================

See :issue:`70514`

Description
===========

The `TCA` configuration `dynamicConfigFile` within the `ctrl` section of a table has been marked as
deprecated and must not be used any longer.


Impact
======

Using `dynamicConfigFile` within the `ctrl` section of a table will trigger a deprecation log entry.


Migration
=========

The setting is typically used in `ext_tables.php` files of extensions. The table configuration (`TCA`) must be moved to an own
file in `Configuration/TCA/<table_name>.php`. The `dynamicConfigFile` setting isn't needed anymore since the whole `TCA` array
definition is in this file.

Furthermore, any other `TCA` manipulation of third party tables must be moved to `Configuration/TCA/Overrides` and no `TCA`
setting must remain in `ext_tables.php`. This is highly encouraged since TYPO3 CMS 6.2 already for performance reasons. If
this change is not applied to extensions, extension `compatibility6` must be loaded or further migration may not be applied
to this portion of `TCA` leading to all sorts of possible issues.


.. index:: PHP-API, TCA
