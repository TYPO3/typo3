.. include:: /Includes.rst.txt

===============================================================================
Feature: #89733 - New PSR-14 events for existing Signal Slots in Core Extension
===============================================================================

See :issue:`89733`

Description
===========

PSR-14 EventDispatching allows for TYPO3 Extensions or PHP packages to extend TYPO3 Core functionality in an exchangeable way.

The following new PSR-14 events have been introduced:

- :php:`TYPO3\CMS\Backend\Authentication\Event\SwitchUserEvent`
- :php:`TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent`
- :php:`TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent`
- :php:`TYPO3\CMS\Backend\Controller\Event\AfterFormEnginePageInitializedEvent`
- :php:`TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent`
- :php:`TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent`
- :php:`TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent`
- :php:`TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent`
- :php:`TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent`
- :php:`TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent`
- :php:`TYPO3\CMS\Core\Tree\Event\ModifyTreeDataEvent`
- :php:`TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent`
- :php:`TYPO3\CMS\Impexp\Event\BeforeImportEvent`
- :php:`TYPO3\CMS\Install\Service\Event\ModifyLanguagePackRemoteBaseUrlEvent`
- :php:`TYPO3\CMS\Linkvalidator\Event\BeforeRecordIsAnalyzedEvent`
- :php:`TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent`
- :php:`TYPO3\CMS\Workspaces\Event\AfterCompiledCacheableDataForWorkspaceEvent`
- :php:`TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent`
- :php:`TYPO3\CMS\Workspaces\Event\GetVersionedDataEvent`
- :php:`TYPO3\CMS\Workspaces\Event\SortVersionedDataEvent`

They replace the existing Extbase-based Signal Slots

- :php:`TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::getSystemInformation`
- :php:`TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::loadMessages`
- :php:`TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider::getPageRenderer`
- :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::preInitAfter`
- :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::initAfter`
- :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfigPreInclude`
- :php:`TYPO3\CMS\Beuser\Controller\BackendUserController::switchUser`
- :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex::setTypoLinkPartsElement`
- :php:`TYPO3\CMS\Core\Database\ReferenceIndex::shouldExcludeTableFromReferenceIndex`
- :php:`TYPO3\CMS\Core\Imaging\IconFactory::buildIconForResourceSignal`
- :php:`TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::PostProcessTreeData`
- :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::tcaIsBeingBuilt`
- :php:`TYPO3\CMS\Impexp\Utility\ImportExportUtility::afterImportExportInitialisation`
- :php:`TYPO3\CMS\Install\Service\SqlExpectedSchemaService::tablesDefinitionIsBeingBuilt`
- :php:`TYPO3\CMS\Lang\Service\TranslationService::postProcessMirrorUrl`
- :php:`TYPO3\CMS\Linkvalidator\LinkAnalyzer::beforeAnalyzeRecord`
- :php:`TYPO3\CMS\Seo\Canonical\CanonicalGenerator::beforeGeneratingCanonical`
- :php:`TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_BeforeCaching`
- :php:`TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_PostProcesss`
- :php:`TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GetDataArray_PostProcesss`
- :php:`TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_SortDataArray_PostProcesss`


Impact
======

It is now possible to add listeners to the new PSR-14 Events which
define a clear API what can be read or modified.

The listeners can be added to the :file:`Configuration/Services.yaml` as
it is done in TYPO3's shipped extensions as well.

.. index:: PHP-API, ext:core
