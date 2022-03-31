.. include:: /Includes.rst.txt

=============================================================================
Feature: #90249 - New PSR-14 events for existing package-related Signal Slots
=============================================================================

See :issue:`90249`

Description
===========

PSR-14-based event dispatching allows for TYPO3 extensions or PHP packages to
extend TYPO3 Core functionality in an exchangeable way.

The following new PSR-14 events have been introduced:

- :php:`TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent`
- :php:`TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent`
- :php:`TYPO3\CMS\Core\Package\Event\AfterPackageDeactivationEvent`
- :php:`TYPO3\CMS\Core\Package\Event\BeforePackageActivationEvent`
- :php:`TYPO3\CMS\Extensionmanager\Event\AfterExtensionDatabaseContentHasBeenImportedEvent`
- :php:`TYPO3\CMS\Extensionmanager\Event\AfterExtensionStaticDatabaseContentHasBeenImportedEvent`
- :php:`TYPO3\CMS\Extensionmanager\Event\AfterExtensionFilesHaveBeenImportedEvent`
- :php:`TYPO3\CMS\Extensionmanager\Event\AvailableActionsForExtensionEvent`

They replace the existing Extbase-based Signal Slots:

- :php:`PackageManagement::packagesMayHaveChanged`
- :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionInstall`
- :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionUninstall`
- :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionT3DImport`
- :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionStaticSqlImport`
- :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionFileImport`
- :php:`TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::willInstallExtensions`
- :php:`TYPO3\CMS\Extensionmanager\ViewHelper\ProcessAvailableActionsViewHelper::processActions`

Impact
======

It is now possible to add listeners to the new PSR-14 Events which
define a clear API what can be read or modified.

The listeners can be added to the :file:`Configuration/Services.yaml` as
it is done in TYPO3's shipped extensions as well.

.. index:: PHP-API, ext:core
