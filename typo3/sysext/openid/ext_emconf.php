<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "openid".
 *
 * Auto generated 25-10-2011 13:11
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'OpenID authentication',
	'description' => 'Adds OpenID authentication to TYPO3',
	'category' => 'services',
	'author' => 'Dmitry Dulepov',
	'author_email' => 'dmitry@typo3.org',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => 'naw_openid,naw_openid_be',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'fe_users,be_users',
	'clearCacheOnLoad' => 0,
	'lockType' => 'system',
	'author_company' => 'TYPO3 core team',
	'version' => '6.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.1.99',
		),
		'conflicts' => array(
			'naw_openid' => '',
			'naw_openid_be' => ''
		),
		'suggests' => array()
	),
	'suggests' => array(),
	'_md5_values_when_last_written' => '',
);
?>