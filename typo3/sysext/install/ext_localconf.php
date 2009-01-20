<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_compatversion.php');

	// register eID script for encryption key AJAX call
$TYPO3_CONF_VARS['FE']['eID_include']['tx_install_eid'] = 'EXT:install/mod/class.tx_install_eid.php';
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'tx_coreupdates_compatversion';

	// not used yet
//require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_notinmenu.php');
//$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['notInMenu_doctype_conversion'] = 'tx_coreupdates_notinmenu';

?>