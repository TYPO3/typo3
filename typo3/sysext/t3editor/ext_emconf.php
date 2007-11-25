<?php

########################################################################
# Extension Manager/Repository config file for ext: "t3editor"
#
# Auto generated 23-11-2007 12:20
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Editor with syntax highlighting',
	'description' => 'JavaScript-driven editor with syntax highlighting for TS, HTML, CSS and more. Based on CodeEditor.',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => 'pmktextarea',
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
	'version' => '0.0.5',
	'_md5_values_when_last_written' => 'a:14:{s:21:"class.tx_t3editor.php";s:4:"bb07";s:12:"ext_icon.gif";s:4:"4cef";s:16:"css/t3editor.css";s:4:"d4f1";s:23:"icons/loader_eeeeee.gif";s:4:"83a4";s:13:"jslib/LICENSE";s:4:"d835";s:14:"jslib/Mochi.js";s:4:"872d";s:24:"jslib/parsejavascript.js";s:4:"5377";s:24:"jslib/parsetyposcript.js";s:4:"7815";s:15:"jslib/select.js";s:4:"6725";s:21:"jslib/stringstream.js";s:4:"e6a5";s:17:"jslib/t3editor.js";s:4:"656c";s:27:"jslib/tokenizejavascript.js";s:4:"1c7a";s:27:"jslib/tokenizetyposcript.js";s:4:"c232";s:13:"jslib/util.js";s:4:"7620";}',
	'constraints' => array(
		'depends' => array(
			'php' => '4.1.0-0.0.0',
			'typo3' => '4.1-0.0.0',
		),
		'conflicts' => array(
			'pmktextarea' => '',
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>