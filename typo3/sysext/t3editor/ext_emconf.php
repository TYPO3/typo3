<?php

########################################################################
# Extension Manager/Repository config file for ext "t3editor".
#
# Auto generated 22-06-2010 12:44
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
	'version' => '1.1.0',
	'_md5_values_when_last_written' => 'a:46:{s:7:"LICENSE";s:4:"c17d";s:12:"ext_icon.gif";s:4:"7eb5";s:17:"ext_localconf.php";s:4:"8798";s:14:"ext_tables.php";s:4:"7dff";s:13:"locallang.xml";s:4:"78cc";s:29:"classes/class.tx_t3editor.php";s:4:"e9c4";s:50:"classes/class.tx_t3editor_hooks_tstemplateinfo.php";s:4:"5956";s:62:"classes/ts_codecompletion/class.tx_t3editor_codecompletion.php";s:4:"abcb";s:59:"classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php";s:4:"20ee";s:23:"icons/loader_eeeeee.gif";s:4:"83a4";s:21:"res/css/csscolors.css";s:4:"3845";s:20:"res/css/jscolors.css";s:4:"e5a0";s:24:"res/css/sparqlcolors.css";s:4:"40ba";s:20:"res/css/t3editor.css";s:4:"9fe2";s:26:"res/css/t3editor_inner.css";s:4:"7b52";s:28:"res/css/typoscriptcolors.css";s:4:"e060";s:21:"res/css/xmlcolors.css";s:4:"847a";s:21:"res/jslib/t3editor.js";s:4:"a6d2";s:28:"res/jslib/codemirror/LICENSE";s:4:"d72d";s:27:"res/jslib/codemirror/README";s:4:"3aca";s:34:"res/jslib/codemirror/codemirror.js";s:4:"6742";s:30:"res/jslib/codemirror/editor.js";s:4:"692d";s:33:"res/jslib/codemirror/highlight.js";s:4:"6d23";s:35:"res/jslib/codemirror/mirrorframe.js";s:4:"a016";s:32:"res/jslib/codemirror/parsecss.js";s:4:"0b95";s:34:"res/jslib/codemirror/parsedummy.js";s:4:"b6e9";s:38:"res/jslib/codemirror/parsehtmlmixed.js";s:4:"ce7e";s:39:"res/jslib/codemirror/parsejavascript.js";s:4:"b422";s:35:"res/jslib/codemirror/parsesparql.js";s:4:"f839";s:39:"res/jslib/codemirror/parsetyposcript.js";s:4:"4808";s:32:"res/jslib/codemirror/parsexml.js";s:4:"2fda";s:30:"res/jslib/codemirror/select.js";s:4:"9083";s:36:"res/jslib/codemirror/stringstream.js";s:4:"b65b";s:32:"res/jslib/codemirror/tokenize.js";s:4:"c008";s:42:"res/jslib/codemirror/tokenizejavascript.js";s:4:"628e";s:42:"res/jslib/codemirror/tokenizetyposcript.js";s:4:"9f7c";s:28:"res/jslib/codemirror/undo.js";s:4:"8097";s:28:"res/jslib/codemirror/util.js";s:4:"27a6";s:47:"res/jslib/ts_codecompletion/completionresult.js";s:4:"ba5f";s:48:"res/jslib/ts_codecompletion/descriptionPlugin.js";s:4:"87a5";s:47:"res/jslib/ts_codecompletion/tscodecompletion.js";s:4:"ef00";s:39:"res/jslib/ts_codecompletion/tsparser.js";s:4:"ab5c";s:36:"res/jslib/ts_codecompletion/tsref.js";s:4:"969e";s:48:"res/jslib/tx_tstemplateinfo/tx_tstemplateinfo.js";s:4:"52c6";s:27:"res/templates/t3editor.html";s:4:"4133";s:19:"res/tsref/tsref.xml";s:4:"3bb5";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.4.0-4.4.99',
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