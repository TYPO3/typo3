<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['sv']['services'] = array(
		'title' => 'LLL:EXT:sv/Resources/Private/Language/locallang.xlf:report_title',
		'description' => 'LLL:EXT:sv/Resources/Private/Language/locallang.xlf:report_description',
		'icon' => 'EXT:sv/Resources/Public/Images/tx_sv_report.png',
		'report' => 'TYPO3\\CMS\\Sv\\Report\\ServicesListReport'
	);
}
