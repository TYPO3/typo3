<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "felogin".
 *
 * Auto generated 25-10-2011 13:10
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Frontend Login for Website Users',
	'description' => 'A template-based plugin to log in Website Users in the Frontend',
	'category' => 'plugin',
	'author' => 'Steffen Kamper',
	'author_email' => 'info@sk-typo3.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'fe_groups,fe_users',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '6.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.1.99',
		),
		'conflicts' => array(),
		'suggests' => array()
	),
	'_md5_values_when_last_written' => '',
	'suggests' => array()
);
?>