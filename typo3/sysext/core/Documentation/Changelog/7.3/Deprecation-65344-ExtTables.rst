
.. include:: ../../Includes.txt

========================================================
Deprecation: #65344 - typo3conf/extTables.php deprecated
========================================================

See :issue:`65344`

Description
===========

The file :file:`typo3conf/extTables.php` which could be used for local TCA modifications has been marked as deprecated.

Setting `$GLOBALS['TYPO3_CONF_VARS']['DB']['extTablesDefinitionScript']` together with the constant
`TYPO3_extTableDef_script` are deprecated and should not be used any longer.


Impact
======

The options and files are typically used for "poor man" `$GLOBALS['TCA']` overrides. This is discouraged
and shouldn't be used any longer.


Migration
=========

There are two options to migrate away from `typo3conf/extTables.php` usage, the first one should be preferred:

* It is good practice to have a project / site specific extension that contains templates, TypoScript and
  other stuff. Create one or more dedicated extensions and use TCA overrides to apply the desired modifications.
  Something like `$GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = FALSE;` should be moved from
  :file:`typo3conf/extTables.php` to :file:`typo3conf/ext/<your_extension>/Configuration/TCA/Overrides/pages.php`.

* Slot the signal `tcaIsBeingBuilt` that is emitted in `ExtensionManagementUtility.php`.


.. index:: PHP-API, TCA, LocalConfiguration
