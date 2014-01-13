<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// TYPO3 6.0 - Create page and TypoScript root template (automatically executed in 123-mode)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['rootTemplate'] = 'TYPO3\\CMS\\Install\\Updates\\RootTemplateUpdate';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'TYPO3\\CMS\\Install\\Updates\\CompatVersionUpdate';
// TYPO3 6.0 - Add new tables for ExtensionManager
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['extensionManagerTables'] = 'TYPO3\\CMS\\Install\\Updates\\ExtensionManagerTables';
// Split backend user and backend groups file permissions to single ones.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['file_permissions'] = 'TYPO3\\CMS\\Install\\Updates\\FilePermissionUpdate';
// Version 6.0: Migrate files content elements to use File Abstraction Layer
// Migrations of tt_content.image DB fields and captions, alt texts, etc. into sys_file_reference records.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_init'] = 'TYPO3\\CMS\\Install\\Updates\\InitUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_images'] = 'TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_uploads'] = 'TYPO3\\CMS\\Install\\Updates\\TtContentUploadsUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_splitMetaData'] = 'TYPO3\\CMS\\Install\\Updates\\FileTableSplittingUpdate';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_truncateProcessedFileTable'] = 'TYPO3\\CMS\\Install\\Updates\\TruncateSysFileProcessedFileTable';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['referenceIntegrity'] = 'TYPO3\\CMS\\Install\\Updates\\ReferenceIntegrityUpdateWizard';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_filemounts'] = 'TYPO3\\CMS\\Install\\Updates\\FilemountUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['fal_identifierhash'] = 'TYPO3\\CMS\\Install\\Updates\\FileIdentifierHashUpdate';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_rtemagicimages'] = 'TYPO3\\CMS\\Install\\Updates\\RteMagicImagesUpdateWizard';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['sysext_file_rtefilelinks'] = 'TYPO3\\CMS\\Install\\Updates\\RteFileLinksUpdateWizard';

// Version 4.7: Migrate the flexforms of MediaElement
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['mediaElementFlexform'] = 'TYPO3\\CMS\\Install\\Updates\\MediaFlexformUpdate';

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService',
	'tablesDefinitionIsBeingBuilt',
	'TYPO3\\CMS\\Install\\Service\\CachingFrameworkDatabaseSchemaService',
	'addCachingFrameworkRequiredDatabaseSchemaToTablesDefinition'
);
$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService',
	'tablesDefinitionIsBeingBuilt',
	'TYPO3\\CMS\\Core\\Category\\CategoryRegistry',
	'addCategoryDatabaseSchemaToTablesDefinition'
);
