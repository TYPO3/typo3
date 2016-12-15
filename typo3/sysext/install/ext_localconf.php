<?php
defined('TYPO3_MODE') or die();

// TYPO3 CMS 7
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['accessRightParameters'] = \TYPO3\CMS\Install\Updates\AccessRightParametersUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendUserStartModule'] = \TYPO3\CMS\Install\Updates\BackendUserStartModuleUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['languageIsoCode'] = \TYPO3\CMS\Install\Updates\LanguageIsoCodeUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['PageShortcutParent'] = \TYPO3\CMS\Install\Updates\PageShortcutParentUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['backendShortcuts'] = \TYPO3\CMS\Install\Updates\MigrateShortcutUrlsAgainUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['processedFilesChecksum'] = \TYPO3\CMS\Install\Updates\ProcessedFileChecksumUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['filesReplacePermission'] = \TYPO3\CMS\Install\Updates\FilesReplacePermissionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['tableCType'] = \TYPO3\CMS\Install\Updates\TableFlexFormToTtContentFieldsUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Install\Updates\FileListInAccessModuleListUpdate::class] = \TYPO3\CMS\Install\Updates\FileListInAccessModuleListUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Install\Updates\FileListIsStartModuleUpdate::class] = \TYPO3\CMS\Install\Updates\FileListIsStartModuleUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['textmediaCType'] = \TYPO3\CMS\Install\Updates\ContentTypesToTextMediaUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Install\Updates\WorkspacesNotificationSettingsUpdate::class] = \TYPO3\CMS\Install\Updates\WorkspacesNotificationSettingsUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['textmediaAssets'] = \TYPO3\CMS\Install\Updates\MigrateMediaToAssetsForTextMediaCe::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['compatibility6Extension'] = \TYPO3\CMS\Install\Updates\Compatibility6ExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['mediaceExtension'] = \TYPO3\CMS\Install\Updates\MediaceExtractionUpdate::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['openidExtension'] = \TYPO3\CMS\Install\Updates\OpenidExtractionUpdate::class;

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class,
    'tablesDefinitionIsBeingBuilt',
    \TYPO3\CMS\Core\Cache\DatabaseSchemaService::class,
    'addCachingFrameworkRequiredDatabaseSchemaForSqlExpectedSchemaService'
);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class,
    'tablesDefinitionIsBeingBuilt',
    \TYPO3\CMS\Core\Category\CategoryRegistry::class,
    'addCategoryDatabaseSchemaToTablesDefinition'
);

// Do not delete this wizard. This makes sure new installations get the TER repository set in the database.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\TYPO3\CMS\Install\Updates\ExtensionManagerTables::class]
    = \TYPO3\CMS\Install\Updates\ExtensionManagerTables::class;
