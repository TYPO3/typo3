<?php

########################################################################
# Extension Manager/Repository config file for ext "linkvalidator".
#
# Auto generated 10-10-2011 22:49
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
	'version' => '1.5.0-rc1',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-0.0.0',
			'info' => '1.0.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:26:{s:9:"ChangeLog";s:4:"9d59";s:16:"ext_autoload.php";s:4:"4efa";s:12:"ext_icon.gif";s:4:"838b";s:17:"ext_localconf.php";s:4:"5e6f";s:14:"ext_tables.php";s:4:"e7a3";s:14:"ext_tables.sql";s:4:"2489";s:13:"locallang.xlf";s:4:"194d";s:44:"classes/class.tx_linkvalidator_processor.php";s:4:"f8f6";s:61:"classes/linktype/class.tx_linkvalidator_linktype_abstract.php";s:4:"1186";s:61:"classes/linktype/class.tx_linkvalidator_linktype_external.php";s:4:"f1b8";s:57:"classes/linktype/class.tx_linkvalidator_linktype_file.php";s:4:"917a";s:62:"classes/linktype/class.tx_linkvalidator_linktype_interface.php";s:4:"3eec";s:61:"classes/linktype/class.tx_linkvalidator_linktype_internal.php";s:4:"9d5f";s:64:"classes/linktype/class.tx_linkvalidator_linktype_linkhandler.php";s:4:"d5d2";s:56:"classes/tasks/class.tx_linkvalidator_tasks_validator.php";s:4:"36d6";s:79:"classes/tasks/class.tx_linkvalidator_tasks_validatoradditionalfieldprovider.php";s:4:"c872";s:14:"doc/manual.sxw";s:4:"996f";s:14:"doc/manual.txt";s:4:"6872";s:54:"modfuncreport/class.tx_linkvalidator_modfuncreport.php";s:4:"3f9f";s:27:"modfuncreport/locallang.xlf";s:4:"85a0";s:31:"modfuncreport/locallang_csh.xlf";s:4:"4569";s:31:"modfuncreport/locallang_mod.xlf";s:4:"a999";s:31:"modfuncreport/mod_template.html";s:4:"4c0f";s:21:"res/linkvalidator.css";s:4:"77b4";s:21:"res/mailtemplate.html";s:4:"c425";s:20:"res/pagetsconfig.txt";s:4:"93e0";}',
	'suggests' => array(
	),
);

?>
