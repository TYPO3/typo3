<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tx_extensionmanager_domain_model_extension'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xml:tx_extensionmanager_domain_model_extension',
		'label' => 'uid',
		'default_sortby' => '',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'Configuration/TCA/Extension.php',
		'hideTable' => TRUE
	),
);

$TCA['tx_extensionmanager_domain_model_repository'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xml:tx_extensionmanager_domain_model_repository',
		'label' => 'uid',
		'default_sortby' => '',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'Configuration/TCA/Repository.php',
		'hideTable' => TRUE,
	),
);

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		$_EXTKEY,
		'tools',
		'extensionmanager', '', array(
			'List' => 'index,ter,showAllVersions',
			'Action' => 'toggleExtensionInstallationState,removeExtension,downloadExtensionZip,downloadExtensionData',
			'Configuration' => 'showConfigurationForm,save',
			'Download' => 'checkDependencies,installFromTer,updateExtension,updateCommentForUpdatableVersions',
			'UpdateFromTer' => 'updateExtensionListFromTer',
			'UploadExtensionFile' => 'form,extract'
		),
		array(
			'access' => 'user,group',
			'icon' => ('EXT:' . $_EXTKEY) . '/ext_icon.gif',
			'labels' => ('LLL:EXT:' . $_EXTKEY) . '/Resources/Private/Language/locallang_mod.xml'
		)
	);
}

?>