<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	// Register wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_form',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/FormWizard/'
	);
}