<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	// Register wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_form',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/FormWizard/'
	);
}