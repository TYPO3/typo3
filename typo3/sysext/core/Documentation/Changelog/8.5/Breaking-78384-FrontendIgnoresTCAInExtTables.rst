.. include:: /Includes.rst.txt

=========================================================
Breaking: #78384 - Frontend ignores TCA in ext_tables.php
=========================================================

See :issue:`78384`

Description
===========

Frontend requests no longer load :file:`ext_tables.php` in requests. The only exception is if a backend user is
logged in to the backend at the same time to initialize the admin panel or frontend editing.


Impact
======

Since especially a not yet cached frontend call relies on initialized :php:`$GLOBALS['TCA']`, changes to `TCA` done
within :file:`ext_tables.php` are now ignored and may fail.


Affected Installations
======================

Extensions that still set, add or remove settings in :php:`$GLOBALS['TCA']` need to be adapted. The install tool
provides test "TCA ext_tables check" to find such extensions.


Migration
=========

In :file:`ext_tables.php` neither writing directly to :php:`$GLOBALS['TCA']` and `$TCA` is allowed, nor writing indirectly
via `ExtensionManagementUtility` methods. An example list of calls and their new positions:

* :php:`$GLOBALS['TCA']['someTable'] = `: A full table `TCA` is added. This must be moved
  to :file:`Configuration/TCA/someTable.php`, see `ext:sys_note` as example.

* :php:`ExtensionManagementUtility::addStaticFile()`: A static file is registered
  in `sys_template`. Add this to :file:`Configuration/TCA/Overrides/sys_template.php`, see `ext:rtehtmlarea` as example.

* :php:`ExtensionManagementUtility::addTCAcolumns()`: Columns are added to a table. Add this
  to :file:`Configuration/TCA/Overrides/<table>.php`, see `ext:felogin` as example.

* :php:`ExtensionManagementUtility::addToAllTCAtypes()`: Fields are added to types. Add this
  to :file:`Configuration/TCA/Overrides/<table>.php`, see `ext:felogin` as example.

* :php:`ExtensionManagementUtility::addPiFlexFormValue()`: A new flex from in `tt_content` is registered. Add
  this to :file:`Configuration/TCA/Overrides/tt_content.php`, see `ext:felogin` as example.

* :php:`ExtensionUtility::registerPlugin()` and :php:`ExtensionManagementUtility::addPlugin`: A new type item
  is added to the `tt_content` table. Add this to :file:`Configuration/TCA/Overrides/tt_content.php`.


.. index:: Frontend, TCA, PHP-API
