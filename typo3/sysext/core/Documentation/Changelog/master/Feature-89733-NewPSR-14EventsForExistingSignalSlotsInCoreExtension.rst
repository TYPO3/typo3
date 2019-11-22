.. include:: ../../Includes.txt

===============================================================================
Feature: #89733 - New PSR-14 events for existing Signal Slots in Core Extension
===============================================================================

See :issue:`89733`

Description
===========

PSR-14 EventDispatching allows for TYPO3 Extensions or PHP packages to extend TYPO3 Core functionality in an exchangeable way.

The following new PSR-14 events have been introduced:

- :php:`TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent`
- :php:`TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent`
- :php:`TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent`
- :php:`TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent`
- :php:`TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent`
- :php:`TYPO3\CMS\Core\Tree\Event\ModifyTreeDataEvent`
- :php:`TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent`

They replace the existing Extbase-based Signal Slots

- :php:`TYPO3\CMS\Core\Imaging\IconFactory::buildIconForResourceSignal`
- :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex::setTypoLinkPartsElement`
- :php:`TYPO3\CMS\Core\Database\ReferenceIndex::shouldExcludeTableFromReferenceIndex`
- :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::tcaIsBeingBuilt`
- :php:`TYPO3\CMS\Install\Service\SqlExpectedSchemaService::tablesDefinitionIsBeingBuilt`
- :php:`TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::PostProcessTreeData`
- :php:`TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::getSystemInformation`
- :php:`TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::loadMessages`


Impact
======

It is now possible to add listeners to the new PSR-14 Events which
define a clear API what can be read or modified.

The listeners can be added to the :file:`Configuration/Services.yaml` as
it is done in TYPO3's shipped extensions as well.

.. index:: PHP-API, ext:core
