<?php
/*
 * Register necessary class names with autoloader
 */
$rtehtmlareaExtensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rtehtmlarea');
return array(
	'tx_rtehtmlarea_api' => $rtehtmlareaExtensionPath . 'class.tx_rtehtmlareaapi.php',
	'tx_rtehtmlarea_base' => $rtehtmlareaExtensionPath . 'class.tx_rtehtmlarea_base.php',
	'tx_rtehtmlarea_softrefproc' => $rtehtmlareaExtensionPath . 'hooks/softref/class.tx_rtehtmlarea_softrefproc.php',
	'tx_rtehtmlarea_statusreport_conflictscheck' => $rtehtmlareaExtensionPath . 'hooks/statusreport/class.tx_rtehtmlarea_statusreport_conflictscheck.php',
	'tx_rtehtmlarea_pi1' => $rtehtmlareaExtensionPath . 'pi1/class.tx_rtehtmlarea_pi1.php',
	'tx_rtehtmlarea_pi2' => $rtehtmlareaExtensionPath . 'pi2/class.tx_rtehtmlarea_pi2.php',
	'tx_rtehtmlarea_pi3' => $rtehtmlareaExtensionPath . 'pi3/class.tx_rtehtmlarea_pi3.php',
	'AccessibilityLinkController' => $rtehtmlareaExtensionPath . 'Classes/Controller/AccessibilityLinkController.php',
	'tx_rtehtmlarea_browse_links' => $rtehtmlareaExtensionPath . 'mod3/class.tx_rtehtmlarea_browse_links.php',
	'tx_rtehtmlarea_select_image' => $rtehtmlareaExtensionPath . 'mod4/class.tx_rtehtmlarea_select_image.php',
	'tx_rtehtmlarea_user' => $rtehtmlareaExtensionPath . 'mod5/class.tx_rtehtmlarea_user.php',
	'tx_rtehtmlarea_parse_html' => $rtehtmlareaExtensionPath . 'mod6/class.tx_rtehtmlarea_parse_html.php'
);
?>