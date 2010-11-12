<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	t3lib_extMgm::addModule('tools', 'em', 'after:layout', t3lib_extMgm::extPath($_EXTKEY) . 'classes/');
		// register Ext.Direct
	$TYPO3_CONF_VARS['SC_OPTIONS']['ExtDirect']['TYPO3.EM.ExtDirect'] = t3lib_extMgm::extPath($_EXTKEY) . 'classes/connection/class.tx_em_connection_extdirectserver.php:tx_em_Connection_ExtDirectServer';

}
?>