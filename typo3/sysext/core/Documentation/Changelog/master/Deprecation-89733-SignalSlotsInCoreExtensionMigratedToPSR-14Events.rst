.. include:: ../../Includes.txt

==============================================================================
Deprecation: #89733 - Signal Slots in Core Extension migrated to PSR-14 events
==============================================================================

See :issue:`89733`

Description
===========

The following Signal Slots have been replaced by new PSR-14 events
which are a 1:1 equivalent:

- :php:`TYPO3\CMS\Core\Imaging\IconFactory::buildIconForResourceSignal`
- :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex::setTypoLinkPartsElement`
- :php:`TYPO3\CMS\Core\Database\ReferenceIndex::shouldExcludeTableFromReferenceIndex`
- :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::tcaIsBeingBuilt`
- :php:`TYPO3\CMS\Install\Service\SqlExpectedSchemaService::tablesDefinitionIsBeingBuilt`
- :php:`TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::PostProcessTreeData`
- :php:`TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::getSystemInformation`
- :php:`TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::loadMessages`

In addition, the following public constant, marking a signal name, is deprecated:

- :php:`TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::SIGNAL_PostProcessTreeData`


Impact
======

Using the mentioned signals will trigger a deprecation warning.


Affected Installations
======================

TYPO3 installations with custom extensions using these signals.

Migration
=========

Use the new PSR-14 alternatives:

- :php:`TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent`
- :php:`TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent`
- :php:`TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent`
- :php:`TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent`
- :php:`TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent`
- :php:`TYPO3\CMS\Core\Tree\Event\ModifyTreeDataEvent`
- :php:`TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent`

.. index:: PHP-API, FullyScanned, ext:core
