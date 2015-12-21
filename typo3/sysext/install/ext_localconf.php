<?php
defined('TYPO3_MODE') or die();

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
