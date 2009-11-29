<?php

########################################################################
# Extension Manager/Repository config file for ext "t3editor".
#
# Auto generated 30-11-2009 00:43
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Editor with syntax highlighting',
	'description' => 'JavaScript-driven editor with syntax highlighting and codecompletion for TS. Based on CodeMirror.',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'doNotLoadInFE' => 1,
	'state' => 'alpha',
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
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:35:{s:7:"LICENSE";s:4:"c17d";s:21:"class.tx_t3editor.php";s:4:"fd42";s:17:"ext_localconf.php";s:4:"5241";s:14:"ext_tables.php";s:4:"7918";s:13:"locallang.xml";s:4:"78cc";s:16:"css/t3editor.css";s:4:"524b";s:22:"css/t3editor_inner.css";s:4:"c37c";s:23:"icons/loader_eeeeee.gif";s:4:"83a4";s:17:"jslib/t3editor.js";s:4:"fed9";s:24:"jslib/codemirror/LICENSE";s:4:"2c10";s:23:"jslib/codemirror/README";s:4:"dbd2";s:30:"jslib/codemirror/codemirror.js";s:4:"3e40";s:26:"jslib/codemirror/editor.js";s:4:"4546";s:31:"jslib/codemirror/mirrorframe.js";s:4:"9944";s:28:"jslib/codemirror/parsecss.js";s:4:"beb3";s:34:"jslib/codemirror/parsehtmlmixed.js";s:4:"8cd8";s:35:"jslib/codemirror/parsejavascript.js";s:4:"664b";s:31:"jslib/codemirror/parsesparql.js";s:4:"8286";s:35:"jslib/codemirror/parsetyposcript.js";s:4:"4808";s:28:"jslib/codemirror/parsexml.js";s:4:"a842";s:26:"jslib/codemirror/select.js";s:4:"a9d4";s:32:"jslib/codemirror/stringstream.js";s:4:"c2a6";s:28:"jslib/codemirror/tokenize.js";s:4:"c008";s:38:"jslib/codemirror/tokenizejavascript.js";s:4:"8219";s:38:"jslib/codemirror/tokenizetyposcript.js";s:4:"9f7c";s:24:"jslib/codemirror/undo.js";s:4:"0a32";s:24:"jslib/codemirror/util.js";s:4:"373e";s:43:"jslib/ts_codecompletion/completionresult.js";s:4:"46a9";s:44:"jslib/ts_codecompletion/descriptionPlugin.js";s:4:"5e86";s:43:"jslib/ts_codecompletion/tscodecompletion.js";s:4:"b6ab";s:35:"jslib/ts_codecompletion/tsparser.js";s:4:"136a";s:32:"jslib/ts_codecompletion/tsref.js";s:4:"41b2";s:58:"lib/ts_codecompletion/class.tx_t3editor_codecompletion.php";s:4:"19c8";s:55:"lib/ts_codecompletion/class.tx_t3editor_tsrefloader.php";s:4:"372a";s:15:"tsref/tsref.xml";s:4:"9f05";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.3.0-4.3.99',
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