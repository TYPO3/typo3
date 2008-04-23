<?php

########################################################################
# Extension Manager/Repository config file for ext: "t3editor"
#
# Auto generated 23-04-2008 11:04
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Editor with syntax highlighting',
	'description' => 'JavaScript-driven editor with syntax highlighting for TS and more. Based on CodeMirror.',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Tobias Liebig',
	'author_email' => 'mail_typo3@etobi.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.0.10',
	'_md5_values_when_last_written' => 'a:29:{s:7:"LICENSE";s:4:"d8dd";s:21:"class.tx_t3editor.php";s:4:"c33a";s:17:"ext_localconf.php";s:4:"dc97";s:23:"icons/loader_eeeeee.gif";s:4:"83a4";s:13:"jslib/LICENSE";s:4:"d835";s:24:"jslib/parsejavascript.js";s:4:"c0dd";s:24:"jslib/parsetyposcript.js";s:4:"5f84";s:15:"jslib/select.js";s:4:"7224";s:21:"jslib/stringstream.js";s:4:"3c02";s:17:"jslib/t3editor.js";s:4:"3c88";s:27:"jslib/tokenizejavascript.js";s:4:"7c98";s:27:"jslib/tokenizetyposcript.js";s:4:"d335";s:13:"jslib/util.js";s:4:"d2a9";s:24:"jslib/codemirror/LICENSE";s:4:"2c10";s:23:"jslib/codemirror/README";s:4:"e540";s:30:"jslib/codemirror/codemirror.js";s:4:"8729";s:26:"jslib/codemirror/editor.js";s:4:"1e72";s:35:"jslib/codemirror/parsejavascript.js";s:4:"23f6";s:35:"jslib/codemirror/parsetyposcript.js";s:4:"4808";s:28:"jslib/codemirror/parsexml.js";s:4:"276a";s:41:"jslib/codemirror/patch.codemirror055.diff";s:4:"50ce";s:26:"jslib/codemirror/select.js";s:4:"6b76";s:32:"jslib/codemirror/stringstream.js";s:4:"665b";s:38:"jslib/codemirror/tokenizejavascript.js";s:4:"7ea7";s:38:"jslib/codemirror/tokenizetyposcript.js";s:4:"b51d";s:24:"jslib/codemirror/undo.js";s:4:"e078";s:24:"jslib/codemirror/util.js";s:4:"8d3e";s:16:"css/t3editor.css";s:4:"f82e";s:22:"css/t3editor_inner.css";s:4:"450b";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-4.2.99',
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