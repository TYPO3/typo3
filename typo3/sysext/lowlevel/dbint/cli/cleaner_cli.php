<?php

if (!defined('TYPO3_cliMode'))	die('You cannot run this script directly!');

require_once(PATH_t3lib.'class.t3lib_refindex.php');
require_once(t3lib_extMgm::extPath('lowlevel').'class.tx_lowlevel_cleaner_core.php');

require(PATH_typo3.'template.php');

	// Call the functionality
$cleanerObj = t3lib_div::makeInstance('tx_lowlevel_cleaner_core');
$cleanerObj->cli_main($_SERVER["argv"]);

?>
