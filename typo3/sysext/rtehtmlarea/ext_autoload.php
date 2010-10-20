<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id: ext_autoload.php $
 */
$extensionPath = t3lib_extMgm::extPath('rtehtmlarea');
return array(
	'tx_rtehtmlarea_statusreport_conflictscheck' => $extensionPath . 'hooks/statusreport/class.tx_rtehtmlarea_statusreport_conflictscheck.php',
);
?>