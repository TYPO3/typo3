<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'FE' && !isset($_REQUEST['eID'])) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->connect(
		'TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository',
		'recordPostRetrieval',
		'TYPO3\\CMS\\Frontend\\Aspect\\FileMetadataOverlayAspect',
		'languageAndWorkspaceOverlay'
	);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.sys_file = 0
	options.saveDocNew.sys_file_metadata = 0
	options.disableDelete.sys_file = 1
');