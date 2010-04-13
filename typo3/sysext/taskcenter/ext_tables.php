<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModulePath('tools_txtaskcenterM1', t3lib_extMgm::extPath($_EXTKEY) . 'task/');
	t3lib_extMgm::addModule('user','task', 'top', t3lib_extMgm::extPath($_EXTKEY) . 'task/');

//	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']['taskcenter']['about'] = array(
//		'title'			=> 'LLL:EXT:taskcenter/locallang.xml:task_help_title',
//		'description'	=> 'LLL:EXT:taskcenter/locallang.xml:task_help_description',
//		'icon'			=> 'EXT:taskcenter/task/icon.gif',
//		'task'			=> 'tx_taskcenter_about'
//	);

	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Taskcenter::saveCollapseState']	= 'EXT:taskcenter/classes/class.tx_taskcenter_status.php:tx_taskcenter_status->saveCollapseState';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Taskcenter::saveSortingState']	= 'EXT:taskcenter/classes/class.tx_taskcenter_status.php:tx_taskcenter_status->saveSortingState';
}
?>