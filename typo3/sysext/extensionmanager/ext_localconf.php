<?php
defined('TYPO3_MODE') or die();

// Register extension list update task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Extensionmanager\\Task\\UpdateExtensionListTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.updateExtensionListTask.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.updateExtensionListTask.description',
	'additionalFields' => '',
);

if (TYPO3_MODE === 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'TYPO3\\CMS\\Extensionmanager\\Command\\ExtensionCommandController';
	if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
		$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
			'willInstallExtensions',
			'TYPO3\\CMS\\Core\\Package\\PackageManager',
			'scanAvailablePackages'
		);
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
			'hasInstalledExtensions',
			'TYPO3\\CMS\\Core\\Package\\PackageManager',
			'updatePackagesForClassLoader'
		);
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility',
			'tablesDefinitionIsBeingBuilt',
			'TYPO3\\CMS\\Core\\Cache\\Cache',
			'addCachingFrameworkRequiredDatabaseSchemaToTablesDefinition'
		);
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility',
			'tablesDefinitionIsBeingBuilt',
			'TYPO3\\CMS\\Core\\Category\\CategoryRegistry',
			'addExtensionCategoryDatabaseSchemaToTablesDefinition'
		);
	}
}
