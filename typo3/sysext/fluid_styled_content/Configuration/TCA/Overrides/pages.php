<?php
defined('TYPO3_MODE') or die();

// Add pageTSconfig
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
	'fluid_styled_content',
	'Configuration/PageTSconfig/NewContentElementWizard.ts',
	'Fluid-based Content Elements'
);
