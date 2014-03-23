<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'tools',
		'extensionmanager', '', array(
			'List' => 'index,ter,showAllVersions,distributions',
			'Action' => 'toggleExtensionInstallationState,removeExtension,downloadExtensionZip,downloadExtensionData',
			'Configuration' => 'showConfigurationForm,save',
			'Download' => 'checkDependencies,installFromTer,installDistribution,updateExtension,updateCommentForUpdatableVersions',
			'UpdateScript' => 'show',
			'UpdateFromTer' => 'updateExtensionListFromTer',
			'UploadExtensionFile' => 'form,extract',
			'Distribution' => 'show'
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);

	// Register extension status report system
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Extension Manager'][] =
		'TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus';

	// Register specific icon for update script button
	\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
		array(
			'update-script' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/Icons/ExtensionUpdateScript.png'
		),
		$_EXTKEY
	);
}
