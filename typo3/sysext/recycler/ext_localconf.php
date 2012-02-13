<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$TYPO3_CONF_VARS['BE']['AJAX']['RecyclerAjaxController::init'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Controller/class.tx_recycler_controller_ajax.php:TYPO3\\CMS\\Recycler\\Controller\\RecyclerAjaxController->init';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Recycler\\Task\\CleanerTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_tasks.xlf:cleanerTaskTitle',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_tasks.xlf:cleanerTaskDescription',
	'additionalFields' => 'TYPO3\\CMS\\Recycler\\Task\\CleanerFieldProvider'
);
?>