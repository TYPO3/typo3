<?php
/**
 * This is a boilerplate of typo3conf/LocalConfiguration.php. It is
 * used as base file during installation and can be overloaded with
 * a package specific file typo3conf/AdditionalFactoryConfiguration.php
 * from eg. the government or introduction package.
 */
return array(
	'BE' => array(
		'explicitADmode' => 'explicitAllow',
		'installToolPassword' => 'bacb98acf97e0b6112b1d1b650b84971',
		'loginSecurityLevel' => 'rsa',
	),
	'DB' => array(
		'extTablesDefinitionScript' => 'extTables.php',
	),
	'EXT' => array(
		'extConf' => array(
			'rsaauth' => 'a:1:{s:18:"temporaryDirectory";s:0:"";}',
			'saltedpasswords' => 'a:7:{s:20:"checkConfigurationFE";s:1:"0";s:20:"checkConfigurationBE";s:1:"0";s:3:"FE.";a:5:{s:7:"enabled";s:1:"1";s:21:"saltedPWHashingMethod";s:41:"TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt";s:11:"forceSalted";s:1:"0";s:15:"onlyAuthService";s:1:"0";s:12:"updatePasswd";s:1:"1";}s:3:"BE.";a:5:{s:7:"enabled";s:1:"1";s:21:"saltedPWHashingMethod";s:41:"TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt";s:11:"forceSalted";s:1:"0";s:15:"onlyAuthService";s:1:"0";s:12:"updatePasswd";s:1:"1";}s:21:"checkConfigurationFE2";s:1:"0";s:21:"checkConfigurationBE2";s:1:"0";s:8:"__meta__";N;}',
		),
		'extListArray' => array(
			'info',
			'perm',
			'func',
			'filelist',
			'about',
			'version',
			'context_help',
			'extra_page_cm_options',
			'impexp',
			'sys_note',
			'tstemplate',
			'func_wizards',
			'wizard_crpages',
			'wizard_sortpages',
			'lowlevel',
			'install',
			'belog',
			'beuser',
			'documentation',
			'aboutmodules',
			'setup',
			'taskcenter',
			'info_pagetsconfig',
			'viewpage',
			'rtehtmlarea',
			'css_styled_content',
			't3skin',
			't3editor',
			'reports',
			'felogin',
			'form',
			'saltedpasswords',
			'rsaauth',
		),
	),
	'FE' => array(
		'loginSecurityLevel' => 'rsa',
	),
	'GFX' => array(
		'jpg_quality' => '80',
	),
	'SYS' => array(
		'compat_version' => '6.2',
		'isInitialInstallationInProgress' => TRUE,
		'setDBinit' => 'SET NAMES utf8;',
		'sitename' => 'New TYPO3 site',
	),
);
?>
