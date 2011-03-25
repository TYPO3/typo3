<?php
/*
 * Register necessary class names with autoloader
 */
$rtehtmlareaExtensionPath = t3lib_extMgm::extPath('rtehtmlarea');
return array(
	'tx_rtehtmlarea_api' => $rtehtmlareaExtensionPath . 'class.tx_rtehtmlareaapi.php',
	'tx_rtehtmlarea_base' => $rtehtmlareaExtensionPath . 'class.tx_rtehtmlarea_base.php',
	'tx_rtehtmlarea_statusreport_conflictscheck' => $rtehtmlareaExtensionPath . 'hooks/statusreport/class.tx_rtehtmlarea_statusreport_conflictscheck.php',
	'tx_rtehtmlarea_pi1' => $rtehtmlareaExtensionPath . 'pi1/class.tx_rtehtmlarea_pi1.php',
	'tx_rtehtmlarea_pi2' => $rtehtmlareaExtensionPath . 'pi2/class.tx_rtehtmlarea_pi2.php',
	'tx_rtehtmlarea_pi3' => $rtehtmlareaExtensionPath . 'pi3/class.tx_rtehtmlarea_pi3.php',
	'tx_rtehtmlarea_browse_links' => $rtehtmlareaExtensionPath . 'mod3/class.tx_rtehtmlarea_browse_links.php',
	'tx_rtehtmlarea_select_image' => $rtehtmlareaExtensionPath . 'mod4/class.tx_rtehtmlarea_select_image.php',
	'tx_rtehtmlarea_user' => $rtehtmlareaExtensionPath . 'mod5/class.tx_rtehtmlarea_user.php',
	'tx_rtehtmlarea_parse_html' => $rtehtmlareaExtensionPath . 'mod6/class.tx_rtehtmlarea_parse_html.php',
);
unset($rtehtmlareaExtensionPath);
?>