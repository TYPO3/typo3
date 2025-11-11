..  include:: /Includes.rst.txt

..  _deprecation-107938-1762181263:

===================================================
Deprecation: #107938 - Deprecate unused XLIFF files
===================================================

See :issue:`107938`

Description
===========

The following XLIFF files have been deprecated, as they are not used in TYPO3 Core anymore:

*  `EXT:backend/Resources/Private/Language/locallang_view_help.xlf`
*  `EXT:backend/Resources/Private/Language/locallang_sitesettings_module.xlf`
*  `EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf`
*  `EXT:backend/Resources/Private/Language/locallang_mod.xlf`
*  `EXT:belog/Resources/Private/Language/locallang_mod.xlf`
*  `EXT:beuser/Resources/Private/Language/locallang_mod.xlf`
*  `EXT:core/Resources/Private/Language/locallang_mod_usertools.xlf`
*  `EXT:core/Resources/Private/Language/locallang_mod_system.xlf`
*  `EXT:core/Resources/Private/Language/locallang_mod_site.xlf`
*  `EXT:core/Resources/Private/Language/locallang_mod_help.xlf`
*  `EXT:core/Resources/Private/Language/locallang_mod_admintools.xlf`
*  `EXT:dashboard/Resources/Private/Language/locallang_mod.xlf`
*  `EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf`
*  `EXT:form/Resources/Private/Language/locallang_module.xlf`
*  `EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf`
*  `EXT:install/Resources/Private/Language/ModuleInstallUpgrade.xlf`
*  `EXT:install/Resources/Private/Language/ModuleInstallSettings.xlf`
*  `EXT:install/Resources/Private/Language/ModuleInstallMaintenance.xlf`
*  `EXT:install/Resources/Private/Language/ModuleInstallEnvironment.xlf`
*  `EXT:install/Resources/Private/Language/BackendModule.xlf`
*  `EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf`
*  `EXT:linkvalidator/Resources/Private/Language/Module/locallang_mod.xlf`
*  `EXT:recycler/Resources/Private/Language/locallang_mod.xlf`

They will be removed with TYPO3 v15.0.

The console command `vendor/bin/typo3 language:domain:list` does not list deprecated language domains,
unless the option `--deprecated` is used.


Impact
======

Using a label reference from one of these files triggers a :php:`E_USER_DEPRECATED` error.


Affected installations
======================

Third party extensions and site packages that use labels from the listed sources will not
be able to display the affected labels with TYPO3 v15.0.


Migration
=========

If the desired string is contained in another language domain, consider to use that
domain. Otherwise, move the required labels into your extension or site package.

..  index:: Backend, Frontend, TCA, TypoScript, NotScanned, ext:core
