<?php

########################################################################
# Extension Manager/Repository config file for ext "indexed_search_mysql".
#
# Auto generated 16-10-2012 14:06
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'MySQL driver for Indexed Search Engine',
	'description' => 'MySQL specific driver for Indexed Search Engine. Allows usage of MySQL-only features like FULLTEXT indexes.',
	'category' => 'misc',
	'shy' => 0,
	'dependencies' => 'indexed_search,cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Michael Stucki',
	'author_email' => 'michael@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '4.7.5',
	'_md5_values_when_last_written' => 'a:5:{s:9:"ChangeLog";s:4:"1bb1";s:32:"class.tx_indexedsearch_mysql.php";s:4:"10bb";s:12:"ext_icon.gif";s:4:"4cbf";s:17:"ext_localconf.php";s:4:"6998";s:14:"ext_tables.sql";s:4:"7f93";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.6-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'indexed_search' => '4.7.0-',
			'cms' => '4.7.0-',
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