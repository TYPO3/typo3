<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	t3lib_extMgm::addModule('tools', 'em', '', t3lib_extMgm::extPath($_EXTKEY) . 'classes/');

		// register Ext.Direct
	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.EM.ExtDirect',
		t3lib_extMgm::extPath($_EXTKEY) . 'classes/connection/class.tx_em_connection_extdirectserver.php:tx_em_Connection_ExtDirectServer',
		'tools_em',
		'admin'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.EMSOAP.ExtDirect',
		t3lib_extMgm::extPath($_EXTKEY) . 'classes/connection/class.tx_em_connection_extdirectsoap.php:tx_em_Connection_ExtDirectSoap',
		'tools_em',
		'admin'
	);

		// register reports check
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Extension Manager'][] = 'tx_em_reports_ExtensionStatus';

	$icons = array(
		'extension-required' => t3lib_extMgm::extRelPath('em') . 'res/icons/extension-required.png'
 	);
 	t3lib_SpriteManager::addSingleIcons($icons, 'em');
}
?>