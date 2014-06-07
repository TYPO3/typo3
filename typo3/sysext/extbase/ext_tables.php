<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// register Extbase dispatcher for modules
	$TBE_MODULES['_dispatcher'][] = 'TYPO3\\CMS\\Extbase\\Core\\ModuleRunnerInterface';
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['extbase'][] = 'TYPO3\\CMS\\Extbase\\Utility\\ExtbaseRequirementsCheckUtility';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Extbase\\Scheduler\\Task'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:task.name',
	'description' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:task.description',
	'additionalFields' => 'TYPO3\\CMS\\Extbase\\Scheduler\\FieldProvider'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue'][] = 'TYPO3\CMS\Extbase\Hook\DataHandler\CheckFlexFormValue';
