<?php
/*
 * Register necessary class names with autoloader
 */
$rtehtmlareaExtensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rtehtmlarea');
return array(
	'AccessibilityLinkController' => $rtehtmlareaExtensionPath . 'Classes/Controller/AccessibilityLinkController.php',
);
