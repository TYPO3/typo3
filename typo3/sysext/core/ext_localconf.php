<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');

if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	$signalSlotDispatcher->connect(
		'TYPO3\\CMS\\Core\\Resource\\ResourceFactory',
		\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PostProcessStorage,
		'TYPO3\\CMS\\Core\\Resource\\Security\\StoragePermissionsAspect',
		'addUserPermissionsToStorage'
	);
	$signalSlotDispatcher->connect(
		'PackageManagement',
		'packagesMayHaveChanged',
		'TYPO3\\CMS\\Core\\Package\\PackageManager',
		'scanAvailablePackages'
	);
}

$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
	\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileDelete,
	'TYPO3\\CMS\\Core\\Resource\\Processing\\FileDeletionAspect',
	'removeFromRepository'
);

$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility',
	'tcaIsBeingBuilt',
	'TYPO3\\CMS\\Core\\Configuration\\TcaBuildingAspect',
	'applyTcaOverrides'
);

unset($signalSlotDispatcher);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['dumpFile'] = 'EXT:core/Resources/PHP/FileDumpEID.php';
