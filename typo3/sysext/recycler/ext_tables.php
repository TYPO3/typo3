<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE') {

		// add module

	t3lib_extMgm::addModulePath('web_txrecyclerM1',t3lib_extMgm::extPath ($_EXTKEY).'mod1/');
	t3lib_extMgm::addModule('web','txrecyclerM1','',t3lib_extMgm::extPath($_EXTKEY).'mod1/');

	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['recyclerGridHeight'] = array(
		'type' => 'text',
		'label' => 'LLL:EXT:recycler/locallang_db.xml:userSettings.RecyclerHeight',
		'default' => 600,
		'csh' => 'tx_recycler_grid_height',
	);
	t3lib_extMgm::addFieldsToUserSettings('recyclerGridHeight', 'after:resizeTextareas_Flexible');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_user_setup','EXT:recycler/locallang_csh_mod.xml');
}
?>