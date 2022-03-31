.. include:: /Includes.rst.txt

===================================================================================
Important: #91888 - System extension "about" merged into "backend" system extension
===================================================================================

See :issue:`91888`

Description
===========

The system extension "about" is removed, and all functionality is migrated into the main backend extension.

The system extension was an addition providing the default module when logging in until TYPO3 v10, unless the Dashboard extension is installed.

The functionality is kept the same, however TYPO3 users upgrading to TYPO3 v11 should be aware
that checks for the extension (via :php:`ExtensionManagementUtility::isLoaded('about')`) will return false, even though all functionality is kept.

When upgrading TYPO3 installation to TYPO3 v11 in composer mode,
it is recommended to first call :bash:`composer remove typo3/cms-about`
on the Command Line before running any :bash:`composer update` or :bash:`composer require` command.

.. index:: CLI, PHP-API, ext:about
