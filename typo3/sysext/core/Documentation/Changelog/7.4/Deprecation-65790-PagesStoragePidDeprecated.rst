
.. include:: /Includes.rst.txt

========================================================
Deprecation: #65790 - Remove pages.storage_pid and logic
========================================================

See :issue:`65790`

Description
===========

The DB field "pages.storage_pid" and its TCA definition have been moved to the compatibility6 extension as the field
and its functionality is discouraged.

Additionally the method `getStorageSiterootPids()` within the PHP class `TypoScriptFrontendController` has been marked
as deprecated. The method is currently only used if the Frontend Login plugin is used without setting
a specific folder where the fe_users records are stored in.


Impact
======

Any usage of this field in any TypoScript, page or the usage of the method mentioned above in any third-party
extension will only work if the compatibility6 extension is installed.

The Frontend Login functionality will throw a deprecation warning if the TypoScript option
`plugin.tx_felogin.storagePid` (via TypoScript directly or the flexform configuration within the plugin) is not set.


Affected installations
======================

All installations making use of `storage_pid` within the pages database table as well as installations using
the Frontend Login plugin without having the storagePid option set.


.. index:: PHP-API, Database, TypoScript, Frontend
