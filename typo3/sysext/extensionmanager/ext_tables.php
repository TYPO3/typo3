<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.Extensionmanager',
		'tools',
		'extensionmanager', '', array(
			'List' => 'index,unresolvedDependencies,ter,showAllVersions,distributions',
			'Action' => 'toggleExtensionInstallationState,installExtensionWithoutSystemDependencyCheck,removeExtension,downloadExtensionZip,downloadExtensionData',
			'Configuration' => 'showConfigurationForm,save,saveAndClose',
			'Download' => 'checkDependencies,installFromTer,installExtensionWithoutSystemDependencyCheck,installDistribution,updateExtension,updateCommentForUpdatableVersions',
			'UpdateScript' => 'show',
			'UpdateFromTer' => 'updateExtensionListFromTer',
			'UploadExtensionFile' => 'form,extract',
			'Distribution' => 'show'
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:extensionmanager/Resources/Public/Icons/module-extensionmanager.png',
			'labels' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf',
		)
	);

	// Register extension status report system
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Extension Manager'][] =
		\TYPO3\CMS\Extensionmanager\Report\ExtensionStatus::class;
}

// Register specific icon for update script button
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
	array(
		'update-script' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('extensionmanager') . 'Resources/Public/Images/Icons/ExtensionUpdateScript.png'
	),
	'extensionmanager'
);
