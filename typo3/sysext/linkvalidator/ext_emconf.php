<?php

########################################################################
# Extension Manager/Repository config file for ext "linkvalidator".
#
# Auto generated 20-10-2010 19:34
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

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
	'lockType' => '',
	'author_company' => 'Connecta AG / cab services ag / Infoglobe',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.1.0-0.0.0',
			'php' => '5.0.0-0.0.0',
			'info' => '1.0.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:26:{s:9:"ChangeLog";s:4:"605b";s:10:"README.txt";s:4:"ee2d";s:16:"ext_autoload.php";s:4:"e79b";s:12:"ext_icon.gif";s:4:"6bc7";s:17:"ext_localconf.php";s:4:"539f";s:14:"ext_tables.php";s:4:"338c";s:14:"ext_tables.sql";s:4:"61f1";s:13:"locallang.xml";s:4:"6351";s:19:"doc/wizard_form.dat";s:4:"ecc2";s:20:"doc/wizard_form.html";s:4:"633b";s:40:"lib/class.tx_linkvalidator_checkbase.php";s:4:"f228";s:49:"lib/class.tx_linkvalidator_checkexternallinks.php";s:4:"13b4";s:45:"lib/class.tx_linkvalidator_checkfilelinks.php";s:4:"03cb";s:49:"lib/class.tx_linkvalidator_checkinternallinks.php";s:4:"4706";s:52:"lib/class.tx_linkvalidator_checklinkhandlerlinks.php";s:4:"2f8b";s:41:"lib/class.tx_linkvalidator_processing.php";s:4:"c757";s:45:"lib/class.tx_linkvalidator_scheduler_link.php";s:4:"00b5";s:68:"lib/class.tx_linkvalidator_scheduler_linkAdditionalFieldProvider.php";s:4:"8f02";s:44:"modfunc1/class.tx_linkvalidator_modfunc1.php";s:4:"e604";s:18:"modfunc1/clear.gif";s:4:"cc11";s:22:"modfunc1/locallang.xml";s:4:"88fb";s:26:"modfunc1/locallang_mod.xml";s:4:"a53c";s:26:"modfunc1/mod_template.html";s:4:"25a9";s:21:"res/linkvalidator.css";s:4:"6e83";s:21:"res/mailTemplate.html";s:4:"51f6";s:20:"res/pageTSconfig.txt";s:4:"8d5f";}',
	'suggests' => array(
	),
);

?>