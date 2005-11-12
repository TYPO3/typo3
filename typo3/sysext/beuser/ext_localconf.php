<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath('beuser').'class.tx_beuser_switchbackuser.php');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_feuserauth.php']['logoff_pre_processing'][] = 'tx_beuser_switchbackuser->switchBack';
?>
