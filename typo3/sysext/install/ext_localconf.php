<?php
defined('TYPO3_MODE') or die();

// All versions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'TYPO3\\CMS\\Install\\Updates\\CompatVersionUpdate';

// TYPO3 7
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendUserStartModule'] = 'TYPO3\\CMS\\Install\\Updates\\BackendUserStartModuleUpdate';

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
