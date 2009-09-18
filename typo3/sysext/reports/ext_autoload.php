<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id$
 */
$extensionPath = t3lib_extMgm::extPath('reports');
return array(
	'tx_reports_statusprovider' => $extensionPath . 'interfaces/interface.tx_reports_statusprovider.php',
	'tx_reports_report' => $extensionPath . 'interfaces/interface.tx_reports_report.php',
	'tx_reports_module' => $extensionPath . 'mod/index.php',
	'tx_reports_reports_status' => $extensionPath . 'reports/class.tx_reports_reports_status.php',
	'tx_reports_reports_status_installtoolstatus' => $extensionPath . 'reports/status/class.tx_reports_reports_status_installtoolstatus.php',
	'tx_reports_reports_status_status' => $extensionPath . 'reports/status/class.tx_reports_reports_status_status.php',
);
?>
