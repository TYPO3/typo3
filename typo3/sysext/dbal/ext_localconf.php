<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php'] = t3lib_extMgm::extPath('dbal') . 'class.ux_t3lib_db.php';
$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_sqlparser.php'] = t3lib_extMgm::extPath('dbal') . 'class.ux_t3lib_sqlparser.php';
$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc'] = t3lib_extMgm::extPath('dbal') . 'class.ux_db_list_extra.php';

// Register a hook for the installer
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['stepOutput'][] = 'EXT:dbal/class.tx_dbal_installtool.php:tx_dbal_installtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['writeLocalconf'][] = 'EXT:dbal/class.tx_dbal_installtool.php:tx_dbal_installtool';
?>
