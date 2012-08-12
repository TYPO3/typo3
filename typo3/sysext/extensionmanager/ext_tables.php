<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tx_extensionmanager_extension'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xml:tx_extensionmanager_extension',
		'label' => 'uid',
		'default_sortby' => '',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/TCA/Extension.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_extensionmanager_extension.gif',
	),
);

if (TYPO3_MODE === 'BE') {
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'tools',
		'extensionmanager',
		'',
		array(
			'List' => 'index,ter,showAllVersions',
			'Action' => 'toggleExtensionInstallationState,removeExtension,downloadExtensionZip,downloadExtensionData',
			'Configuration' => 'showConfigurationForm,save',
			'Download' => 'checkDependencies,installFromTer,updateExtension,updateCommentForUpdatableVersions',
			'UpdateFromTer' => 'updateExtensionListFromTer',
			'UploadExtensionFile' => 'form,extract'
		),
		array(
			'access' => 'user,group',
			'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml')
	);
}

?>