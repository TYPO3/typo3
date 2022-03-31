.. include:: /Includes.rst.txt

============================================================================
Deprecation: #90249 - Package related Signal Slots migrated to PSR-14 events
============================================================================

See :issue:`90249`

Description
===========

The following Signal Slots have been replaced by new PSR-14 events
which can be used as 1:1 equivalents:

* :php:`PackageManagement::packagesMayHaveChanged`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionInstall`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionUninstall`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionT3DImport`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionStaticSqlImport`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionFileImport`
* :php:`TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::willInstallExtensions`
* :php:`TYPO3\CMS\Extensionmanager\ViewHelper\ProcessAvailableActionsViewHelper::processActions`


Impact
======

Using the mentioned signals will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions using these signals.


Migration
=========

Use the new PSR-14 alternatives:

* :php:`TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent`
* :php:`TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent`
* :php:`TYPO3\CMS\Core\Package\Event\AfterPackageDeactivationEvent`
* :php:`TYPO3\CMS\Core\Package\Event\BeforePackageActivationEvent`
* :php:`TYPO3\CMS\Extensionmanager\Event\AfterExtensionDatabaseContentHasBeenImportedEvent`
* :php:`TYPO3\CMS\Extensionmanager\Event\AfterExtensionStaticDatabaseContentHasBeenImportedEvent`
* :php:`TYPO3\CMS\Extensionmanager\Event\AfterExtensionFilesHaveBeenImportedEvent`
* :php:`TYPO3\CMS\Extensionmanager\Event\AvailableActionsForExtensionEvent`

.. index:: PHP-API, FullyScanned, ext:core
