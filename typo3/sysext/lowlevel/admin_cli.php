<?php

if (!defined('TYPO3_cliMode'))	die('You cannot run this script directly!');

require_once(t3lib_extMgm::extPath('lowlevel').'class.tx_lowlevel_admin_core.php');

	// Call the functionality
$adminObj = t3lib_div::makeInstance('tx_lowlevel_admin_core');
$adminObj->cli_main($_SERVER["argv"]);

?>