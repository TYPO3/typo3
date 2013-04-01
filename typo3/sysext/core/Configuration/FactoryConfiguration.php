<?php
/**
 * This is a boilerplate of typo3conf/LocalConfiguration.php. It is
 * used as base file during installation and can be overloaded with
 * a package specific file typo3conf/AdditionalFactoryConfiguration.php
 * from eg. the government or introduction package.
 */
return array(
	'BE' => array(
		'installToolPassword' => 'bacb98acf97e0b6112b1d1b650b84971',
	),
	'DB' => array(
		'extTablesDefinitionScript' => 'extTables.php',
	),
	'EXT' => array(
		'extListArray' => array(
			'info',
			'perm',
			'func',
			'filelist',
			'about',
			'version',
			'tsconfig_help',
			'context_help',
			'extra_page_cm_options',
			'impexp',
			'sys_note',
			'tstemplate',
			'tstemplate_ceditor',
			'tstemplate_info',
			'tstemplate_objbrowser',
			'tstemplate_analyzer',
			'func_wizards',
			'wizard_crpages',
			'wizard_sortpages',
			'lowlevel',
			'install',
			'belog',
			'beuser',
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
		),
	),
	'SYS' => array(
		'sitename' => 'New TYPO3 site',
	),
);
?>