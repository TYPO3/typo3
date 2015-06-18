<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');

if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	// FAL SECURITY CHECKS
	$signalSlotDispatcher->connect(
		'TYPO3\\CMS\\Core\\Resource\\ResourceFactory',
		\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PostProcessStorage,
		'TYPO3\\CMS\\Core\\Resource\\Security\\StoragePermissionsAspect',
		'addUserPermissionsToStorage'
	);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'TYPO3\\CMS\\Core\\Resource\\Security\\FileMetadataPermissionsAspect';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck'][] = 'TYPO3\\CMS\\Core\\Resource\\Security\\FileMetadataPermissionsAspect->isAllowedToShowEditForm';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['checkAccess'][] = 'TYPO3\\CMS\\Core\\Resource\\Security\\FileMetadataPermissionsAspect->isAllowedToShowEditForm';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = 'TYPO3\\CMS\\Core\\Resource\\Security\\FileMetadataPermissionsAspect';

	// PACKAGE MANAGEMENT
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
	'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
	\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
	'TYPO3\\CMS\\Core\\Resource\\Processing\\FileDeletionAspect',
	'cleanupProcessedFilesPostFileAdd'
);

$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
	\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace,
	'TYPO3\\CMS\\Core\\Resource\\Processing\\FileDeletionAspect',
	'cleanupProcessedFilesPostFileReplace'
);

unset($signalSlotDispatcher);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['dumpFile'] = 'EXT:core/Resources/PHP/FileDumpEID.php';
