<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'system',
		'txreportsM1',
		'',
		array(
			'Report' => 'index,detail'
		), array(
			'access' => 'admin',
			'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-reports.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf'
		)
	);
	$statusReport = array(
		'title' => 'LLL:EXT:reports/reports/locallang.xlf:status_report_title',
		'icon' => 'EXT:reports/Resources/Public/Icons/module-reports.png',
		'description' => 'LLL:EXT:reports/reports/locallang.xlf:status_report_description',
		'report' => \TYPO3\CMS\Reports\Report\Status\Status::class
	);
	if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'] = array();
	}
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'], $statusReport);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['typo3'][] = \TYPO3\CMS\Reports\Report\Status\Typo3Status::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['system'][] = \TYPO3\CMS\Reports\Report\Status\SystemStatus::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['security'][] = \TYPO3\CMS\Reports\Report\Status\SecurityStatus::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['configuration'][] = \TYPO3\CMS\Reports\Report\Status\ConfigurationStatus::class;
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['fal'][] = \TYPO3\CMS\Reports\Report\Status\FalStatus::class;
}
