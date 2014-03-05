<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	// Register report module additions
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['typo3'][] = 'TYPO3\\CMS\\Install\\Report\\InstallStatusReport';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['system'][] = 'TYPO3\\CMS\\Install\\Report\\EnvironmentStatusReport';

	// Register backend module
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'system',
		'install', '', array(
			'BackendModule' => 'index, showEnableInstallToolButton, enableInstallTool',
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/Icon/BackendModule.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/BackendModule.xlf',
		)
	);
}
