.. include:: /Includes.rst.txt

.. _deprecation-98487-1664575576:

===========================================================================
Deprecation: #98487 - ExtensionManagementUtility::allowTableOnStandardPages
===========================================================================

See :issue:`98487`

Description
===========

The API method :php:`ExtensionManagementUtility::allowTableOnStandardPages` which
was used in `ext_tables.php` files of extensions registering custom records available
on any page type has been marked as deprecated.

Impact
======

Calling the method will still work, however it is recommended to add a specific flag
to the tables TCA to be compatible with multiple TYPO3 versions. No deprecation notice
will be triggered.

Affected installations
======================

TYPO3 installations with custom extensions creating custom TCA records to be added
on any page type calling the affected method.

Migration
=========

Set new TCA option :php:`$GLOBALS['TCA'][$table]['ctrl']['security']['ignorePageTypeRestriction']`
of a custom TCA table to keep the same behaviour as in previous TYPO3 versions.

.. index:: PHP-API, FullyScanned, ext:core
