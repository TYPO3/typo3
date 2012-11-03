<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('reports');
return array(
	'tx_reports_statusprovider' => $extensionPath . 'interfaces/interface.tx_reports_statusprovider.php',
	'tx_reports_report' => $extensionPath . 'interfaces/interface.tx_reports_report.php',
	'tx_reports_reports_status' => $extensionPath . 'reports/class.tx_reports_reports_status.php',
	'tx_reports_reports_status_typo3status' => $extensionPath . 'reports/status/class.tx_reports_reports_status_typo3status.php',
	'tx_reports_reports_status_systemstatus' => $extensionPath . 'reports/status/class.tx_reports_reports_status_systemstatus.php',
	'tx_reports_reports_status_securitystatus' => $extensionPath . 'reports/status/class.tx_reports_reports_status_securitystatus.php',
	'tx_reports_reports_status_configurationstatus' => $extensionPath . 'reports/status/class.tx_reports_reports_status_configurationstatus.php',
	'tx_reports_reports_status_status' => $extensionPath . 'reports/status/class.tx_reports_reports_status_status.php',
	'tx_reports_tasks_systemstatusupdatetask' => $extensionPath . 'tasks/class.tx_reports_tasks_systemstatusupdatetask.php',
	'tx_reports_tasks_systemstatusupdatetasknotificationemailfield' => $extensionPath . 'tasks/class.tx_reports_tasks_systemstatusupdatetasknotificationemailfield.php'
);
?>