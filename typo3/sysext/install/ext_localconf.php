<?php
defined('TYPO3_MODE') or die();

// All versions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = \TYPO3\CMS\Install\Updates\CompatVersionUpdate::class;

// TYPO3 CMS 7
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendUserStartModule'] = \TYPO3\CMS\Install\Updates\BackendUserStartModuleUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['languageIsoCode'] = \TYPO3\CMS\Install\Updates\LanguageIsoCodeUpdate::class;

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
	\TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class,
	'tablesDefinitionIsBeingBuilt',
	\TYPO3\CMS\Install\Service\CachingFrameworkDatabaseSchemaService::class,
	'addCachingFrameworkRequiredDatabaseSchemaToTablesDefinition'
);
$signalSlotDispatcher->connect(
	\TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class,
	'tablesDefinitionIsBeingBuilt',
	\TYPO3\CMS\Core\Category\CategoryRegistry::class,
	'addCategoryDatabaseSchemaToTablesDefinition'
);
