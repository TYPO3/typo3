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
		'loginSecurityLevel' => 'rsa',
	),
	'DB' => array(
		'extTablesDefinitionScript' => 'extTables.php',
	),
	'EXT' => array(
		'extConf' => array(
			'rsaauth' => 'a:1:{s:18:"temporaryDirectory";s:0:"";}',
			'saltedpasswords' => serialize(array(
				'BE.' => array(
					'saltedPWHashingMethod' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt',
					'forceSalted' => 0,
					'onlyAuthService' => 0,
					'updatePasswd' => 1,
				),
				'FE.' => array(
					'enabled' => 1,
					'saltedPWHashingMethod' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt',
					'forceSalted' => 0,
					'onlyAuthService' => 0,
					'updatePasswd' => 1,
				),
			)),
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
