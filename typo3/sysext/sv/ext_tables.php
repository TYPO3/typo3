<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['sv']['services'] = array(
		'title' => 'LLL:EXT:sv/reports/locallang.xml:report_title',
		'description' => 'LLL:EXT:sv/reports/locallang.xml:report_description',
		'icon' => 'EXT:sv/reports/tx_sv_report.png',
		'report' => 'TYPO3\\CMS\\Sv\\Report\\ServicesListReport'
	);
}
?>