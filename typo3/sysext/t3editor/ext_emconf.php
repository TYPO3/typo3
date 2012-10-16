<?php

########################################################################
# Extension Manager/Repository config file for ext "t3editor".
#
# Auto generated 16-10-2012 14:08
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Editor with syntax highlighting',
	'description' => 'JavaScript-driven editor with syntax highlighting and codecompletion. Based on CodeMirror.',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Tobias Liebig, Stephan Petzl, Christian Kartnig',
	'author_email' => 'mail_typo3@etobi.de, spetzl@gmx.at, office@hahnepeter.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '4.7.5',
	'_md5_values_when_last_written' => 'a:30:{s:12:"ext_icon.gif";s:4:"7eb5";s:17:"ext_localconf.php";s:4:"3b52";s:14:"ext_tables.php";s:4:"70e5";s:7:"LICENSE";s:4:"c17d";s:13:"locallang.xlf";s:4:"c6fb";s:29:"classes/class.tx_t3editor.php";s:4:"3ead";s:44:"classes/class.tx_t3editor_hooks_fileedit.php";s:4:"1fed";s:50:"classes/class.tx_t3editor_hooks_tstemplateinfo.php";s:4:"ea5a";s:45:"classes/class.tx_t3editor_tceforms_wizard.php";s:4:"c80e";s:62:"classes/ts_codecompletion/class.tx_t3editor_codecompletion.php";s:4:"9965";s:59:"classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php";s:4:"94f6";s:23:"icons/loader_eeeeee.gif";s:4:"83a4";s:21:"res/css/csscolors.css";s:4:"3845";s:20:"res/css/jscolors.css";s:4:"e5a0";s:24:"res/css/sparqlcolors.css";s:4:"40ba";s:20:"res/css/t3editor.css";s:4:"454d";s:26:"res/css/t3editor_inner.css";s:4:"7b52";s:28:"res/css/typoscriptcolors.css";s:4:"e060";s:21:"res/css/xmlcolors.css";s:4:"847a";s:21:"res/jslib/fileedit.js";s:4:"e87e";s:21:"res/jslib/t3editor.js";s:4:"7db5";s:45:"res/jslib/parse_typoscript/parsetyposcript.js";s:4:"c8af";s:48:"res/jslib/parse_typoscript/tokenizetyposcript.js";s:4:"6ba8";s:47:"res/jslib/ts_codecompletion/completionresult.js";s:4:"ba5f";s:48:"res/jslib/ts_codecompletion/descriptionPlugin.js";s:4:"1df2";s:47:"res/jslib/ts_codecompletion/tscodecompletion.js";s:4:"a603";s:39:"res/jslib/ts_codecompletion/tsparser.js";s:4:"ab5c";s:36:"res/jslib/ts_codecompletion/tsref.js";s:4:"969e";s:27:"res/templates/t3editor.html";s:4:"4133";s:19:"res/tsref/tsref.xml";s:4:"53c3";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>