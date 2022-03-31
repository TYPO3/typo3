
.. include:: /Includes.rst.txt

=============================================================================
Breaking: #72572 - Remove more deprecated miscellaneous functions and options
=============================================================================

See :issue:`72572`

Description
===========

Removed more deprecated miscellaneous functions and options:

* Linking to a php file directly in `ClickMenu` has been removed.
* Functionality of `$TBE_STYLES['background']` has been removed.
* The option `DocumentTemplate->JScodeLibArray` has been removed.
* The option `$TYPO3_CONF_VARS[SYS][displayErrors]` set to "2" will throw an exception.
* The deprecated icons fallback for `actions-system-refresh` and `actions-system-extension-update-disabled` has been removed.
* An extension may not refer to `ext:cms` in composer.json or ext_emconf.php file. The fallback has been removed.
* The method `loadNewTcaColumnsConfigFiles` has been removed.
* Usage of the field "static_lang_isocode" has stopped working. Use the built-in language field "language_isocode" in sys_language records.


Impact
======

Using one of the mentioned options or methods will result in a fatal error or won't have any effect anymore.

Registration of `TCA` within `ext_tables.php` now finally stops working and
code to resolve `dynamicConfigFile` option from `TCA` has been removed.
Extensions still relying on this will fail.


Affected Installations
======================

Instances which use one of the methods above or use one of the removed options.


Migration
=========

For `DocumentTemplate->JScodeLibArray` use PageRenderer instead.

If the option `$TYPO3_CONF_VARS[SYS][displayErrors]` is set to "2" use "-1" instead.

All table definitions should be moved to <your_extension>/Configuration/TCA/<table_name>

.. index:: PHP-API, TCA, Backend, LocalConfiguration
