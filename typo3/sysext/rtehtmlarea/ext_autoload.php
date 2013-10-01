<?php
/*
 * Register necessary class names with autoloader
 */
$rtehtmlareaExtensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rtehtmlarea');
return array(
	'tx_rtehtmlarea_pi1' => $rtehtmlareaExtensionPath . 'pi1/class.tx_rtehtmlarea_pi1.php',
	'tx_rtehtmlarea_pi2' => $rtehtmlareaExtensionPath . 'pi2/class.tx_rtehtmlarea_pi2.php',
	'tx_rtehtmlarea_pi3' => $rtehtmlareaExtensionPath . 'pi3/class.tx_rtehtmlarea_pi3.php',
	'AccessibilityLinkController' => $rtehtmlareaExtensionPath . 'Classes/Controller/AccessibilityLinkController.php',
);
