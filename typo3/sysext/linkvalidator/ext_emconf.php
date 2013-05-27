<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "linkvalidator".
 *
 * Auto generated 23-10-2011 17:09
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Linkvalidator',
	'description' => 'Linkvalidator checks the links in your website for validity. It can validate all kinds of links: internal, external and file links. Scheduler is supported to run Linkvalidator via Cron including the option to send status mails, if broken links were detected.',
	'category' => 'module',
	'author' => 'Jochen Rieger / Dimitri König / Michael Miousse',
	'author_email' => 'j.rieger@connecta.ag, mmiousse@infoglobe.ca',
	'shy' => '',
	'dependencies' => 'info',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'doNotLoadInFE' => 1,
	'lockType' => '',
	'author_company' => 'Connecta AG / cab services ag / Infoglobe',
	'version' => '6.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.1.99',
			'info' => '6.1.0-6.1.99',
		),
		'conflicts' => array(),
		'suggests' => array()
	),
	'_md5_values_when_last_written' => '',
	'suggests' => array()
);
?>