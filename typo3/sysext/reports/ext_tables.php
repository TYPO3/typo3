<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModulePath('tools_txreportsM1', t3lib_extMgm::extPath($_EXTKEY) . 'mod/');
	t3lib_extMgm::addModule('tools', 'txreportsM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod/');

	$statusReport = array(
		'title'       => 'LLL:EXT:reports/reports/locallang.xml:status_report_title',
		'description' => 'LLL:EXT:reports/reports/locallang.xml:status_report_description',
		'report'      => 'tx_reports_reports_Status'
	);

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'] = array_merge(
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'],
		$statusReport
	);

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['typo3'][] = 'tx_reports_reports_status_Typo3Status';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['system'][] = 'tx_reports_reports_status_SystemStatus';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['security'][] = 'tx_reports_reports_status_SecurityStatus';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['configuration'][] = 'tx_reports_reports_status_ConfigurationStatus';

	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Reports::saveCollapseState'] = 'EXT:reports/reports/class.tx_reports_reports_status.php:tx_reports_reports_Status->saveCollapseState';
}

?>