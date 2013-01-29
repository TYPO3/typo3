<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tx_extensionmanager_domain_model_extension'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xml:tx_extensionmanager_domain_model_extension',
		'label' => 'uid',
		'default_sortby' => '',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Extension.php',
		'hideTable' => TRUE
	),
);

$TCA['tx_extensionmanager_domain_model_repository'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xml:tx_extensionmanager_domain_model_repository',
		'label' => 'uid',
		'default_sortby' => '',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Repository.php',
		'hideTable' => TRUE,
	),
);

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'tools',
		'extensionmanager', '', array(
			'List' => 'index,ter,showAllVersions',
			'Action' => 'toggleExtensionInstallationState,removeExtension,downloadExtensionZip,downloadExtensionData',
			'Configuration' => 'showConfigurationForm,save',
			'Download' => 'checkDependencies,installFromTer,updateExtension,updateCommentForUpdatableVersions',
			'UpdateScript' => 'show',
			'UpdateFromTer' => 'updateExtensionListFromTer',
			'UploadExtensionFile' => 'form,extract'
		),
		array(
			'access' => 'user,group',
			'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml',
		)
	);

	// Register extension status report system
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Extension Manager'][] =
		'TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus';

	// Register specific icon for update script button
	t3lib_SpriteManager::addSingleIcons(
		array(
			'update-script' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Images/Icons/ExtensionUpdateScript.png'
		),
		$_EXTKEY
	);
}
?>