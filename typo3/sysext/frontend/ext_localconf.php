<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'FE' && !isset($_REQUEST['eID'])) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->connect(
		'TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository',
		'recordPostRetrieval',
		'TYPO3\\CMS\\Frontend\\Aspect\\FileMetadataOverlayAspect',
		'languageAndWorkspaceOverlay'
	);
}
