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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
	'frontend',
	'setup',
	'config.extTarget = _top'
	. LF . 'config.uniqueLinkVars = 1'
);


if (TYPO3_MODE === 'FE') {

	// Register eID provider for showpic
	$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_cms_showpic'] = 'EXT:frontend/Resources/PHP/Eid/ShowPic.php';
	// Register eID provider for ExtDirect for the frontend
	$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['ExtDirect'] = 'EXT:frontend/Resources/PHP/Eid/ExtDirect.php';

	// Register the core media wizard provider
	\TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderManager::registerMediaWizardProvider('TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProvider');
}
