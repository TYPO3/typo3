<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_compatversion.php');
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'tx_coreupdates_compatversion';

	// not used yet
//require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_notinmenu.php');
//$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['notInMenu_doctype_conversion'] = 'tx_coreupdates_notinmenu';

?>