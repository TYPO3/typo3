<?php
defined('TYPO3_MODE') or die();

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	$signalSlotDispatcher->connect(
		\TYPO3\CMS\Core\Resource\ResourceFactory::class,
		\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PostProcessStorage,
		\TYPO3\CMS\Core\Resource\Security\StoragePermissionsAspect::class,
		'addUserPermissionsToStorage'
	);
	$signalSlotDispatcher->connect(
		'PackageManagement',
		'packagesMayHaveChanged',
		\TYPO3\CMS\Core\Package\PackageManager::class,
		'scanAvailablePackages'
	);
}

$signalSlotDispatcher->connect(
	\TYPO3\CMS\Core\Resource\ResourceStorage::class,
	\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileDelete,
	\TYPO3\CMS\Core\Resource\Processing\FileDeletionAspect::class,
	'removeFromRepository'
);

$signalSlotDispatcher->connect(
	\TYPO3\CMS\Core\Resource\ResourceStorage::class,
	\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
	\TYPO3\CMS\Core\Resource\Processing\FileDeletionAspect::class,
	'cleanupProcessedFilesPostFileAdd'
);

$signalSlotDispatcher->connect(
	\TYPO3\CMS\Core\Resource\ResourceStorage::class,
	\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace,
	\TYPO3\CMS\Core\Resource\Processing\FileDeletionAspect::class,
	'cleanupProcessedFilesPostFileReplace'
);

if (!\TYPO3\CMS\Core\Core\Bootstrap::usesComposerClassLoading()) {
	$buildAliasMap = function() {
		$bootstrap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance();
		$classAliasMap = $bootstrap->getEarlyInstance(\TYPO3\CMS\Core\Core\ClassAliasMap::class);
		$classAliasMap->buildStaticMappingFile();
	};
	$signalSlotDispatcher->connect(
		\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class,
		'hasInstalledExtensions',
		$buildAliasMap
	);

	$signalSlotDispatcher->connect(
		\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
		'afterExtensionUninstall',
		$buildAliasMap
	);
	unset($buildAliasMap);
}

unset($signalSlotDispatcher);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['dumpFile'] = 'EXT:core/Resources/PHP/FileDumpEID.php';

/** @var \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry $rendererRegistry */
$rendererRegistry = \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance();
$rendererRegistry->registerRendererClass(\TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer::class);
$rendererRegistry->registerRendererClass(\TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer::class);
