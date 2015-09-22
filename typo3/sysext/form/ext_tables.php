<?php
defined('TYPO3_MODE') or die();

// Register static TypoScript resource
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('form', 'Configuration/TypoScript/', 'Default TS');

if (TYPO3_MODE === 'BE') {
	// Register form wizard as backend module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_form',
		'EXT:form/Modules/Wizards/FormWizard/'
	);
}
