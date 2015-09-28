<?php
defined('TYPO3_MODE') or die();

// Add pageTSconfig
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
	'css_styled_content',
	'Configuration/PageTSconfig/NewContentElementWizard.ts',
	'CSS-based Content Elements'
);
