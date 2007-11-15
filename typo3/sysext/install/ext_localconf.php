<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

define('INSTALLER_PATH', t3lib_extMgm::extPath('install'));
define('INSTALLER_MOD_PATH', INSTALLER_PATH.'modules/');

	// Here the modules for the Installer will be registered
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['modules'] = array (
	'installer' => INSTALLER_MOD_PATH.'installer/class.tx_install_module_installer.php:tx_install_module_installer',
	'setup' => INSTALLER_MOD_PATH.'setup/class.tx_install_module_setup.php:tx_install_module_setup',
	
	'database' => INSTALLER_MOD_PATH.'database/class.tx_install_module_database.php:tx_install_module_database',
	'gfx' => INSTALLER_MOD_PATH.'gfx/class.tx_install_module_gfx.php:tx_install_module_gfx',
	'php' => INSTALLER_MOD_PATH.'php/class.tx_install_module_php.php:tx_install_module_php',
	'directories' => INSTALLER_MOD_PATH.'directories/class.tx_install_module_directories.php:tx_install_module_directories',
	'system' => INSTALLER_MOD_PATH.'system/class.tx_install_module_system.php:tx_install_module_system',
);

/*
	// Here are the update-classes will be registered. This is something from the old install-tool
	// and is maybe obsolete with the new version. We'll see

require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_compatversion.php');
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'tx_coreupdates_compatversion';

	// not used yet
//require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_notinmenu.php');
//$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['notInMenu_doctype_conversion'] = 'tx_coreupdates_notinmenu';

*/
?>