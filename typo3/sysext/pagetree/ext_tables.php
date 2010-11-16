<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	$modules = array(
		'web_layout', 'web_view', 'web_list', 'web_info', 'web_perm', 'web_func', 'web_ts',
		'web_WorkspacesWorkspaces', 'web_txrecyclerM1', 'web_txversionM1'
	);
	foreach ($modules as $module) {
		t3lib_extMgm::addNavigationComponent($module, 'typo3-pagetree', array(
			'TYPO3.Components.PageTree'
		));
	}

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']['TYPO3.Components.PageTree.DataProvider'] =
		t3lib_extMgm::extPath($_EXTKEY) . 'extdirect/dataprovider/class.tx_pagetree_dataprovider_pagetree.php:tx_pagetree_dataprovider_Pagetree';
}

?>