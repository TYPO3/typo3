<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "cms".
 *
 * Auto generated 25-10-2011 13:10
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'TYPO3 CMS Frontend (TypoScript)',
	'description' => 'The core TypoScript Content Management engine in TYPO3.
This should probably not be disabled. But the point is that Typo3 is able to work as a framework for... anything without this (and the whole tslib/ frontend which is tied to this extension). A LOT of the other extensions - in particular all plugins - are dependant on this extension being loaded.',
	'category' => 'be',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => 'top',
	'loadOrder' => '',
	'module' => 'layout',
	'state' => 'stable',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'pages',
	'clearCacheOnLoad' => 1,
	'lockType' => 'S',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'CURBY SOFT Multimedie',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '6.1.0',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.1.99',
		),
		'conflicts' => array(),
		'suggests' => array()
	),
	'suggests' => array()
);
?>