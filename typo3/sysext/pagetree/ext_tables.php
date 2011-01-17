<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {

	t3lib_extMgm::addNavigationComponent('web', 'typo3-pagetree', array(
		'TYPO3.Components.PageTree'
	));

	$absoluteExtensionPath = t3lib_extMgm::extPath($_EXTKEY);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'] = array_merge(
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'], array(
			'TYPO3.Components.PageTree.DataProvider' =>
				$absoluteExtensionPath . 'classes/extdirect/class.tx_pagetree_extdirect_tree.php:tx_pagetree_ExtDirect_Tree',
			'TYPO3.Components.PageTree.Commands' =>
				$absoluteExtensionPath . 'classes/extdirect/class.tx_pagetree_extdirect_tree.php:tx_pagetree_ExtDirect_Commands',
			'TYPO3.Components.PageTree.ContextMenuDataProvider' =>
				$absoluteExtensionPath . 'classes/extdirect/class.tx_pagetree_extdirect_contextmenu.php:tx_pagetree_ExtDirect_ContextMenu',
		)
	);
}

?>
