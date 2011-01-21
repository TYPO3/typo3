<?php

########################################################################
# Extension Manager/Repository config file for ext "linkvalidator".
#
# Auto generated 12-01-2011 17:43
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
	'_md5_values_when_last_written' => 'a:26:{s:9:"ChangeLog";s:4:"ed60";s:16:"ext_autoload.php";s:4:"619e";s:12:"ext_icon.gif";s:4:"6bc7";s:17:"ext_localconf.php";s:4:"0598";s:14:"ext_tables.php";s:4:"6d40";s:14:"ext_tables.sql";s:4:"317e";s:13:"locallang.xml";s:4:"ec0a";s:45:"classes/class.tx_linkvalidator_processing.php";s:4:"611a";s:63:"classes/linktypes/class.tx_linkvalidator_linktypes_abstract.php";s:4:"7f7e";s:63:"classes/linktypes/class.tx_linkvalidator_linktypes_external.php";s:4:"bdfa";s:59:"classes/linktypes/class.tx_linkvalidator_linktypes_file.php";s:4:"ee97";s:64:"classes/linktypes/class.tx_linkvalidator_linktypes_interface.php";s:4:"c385";s:63:"classes/linktypes/class.tx_linkvalidator_linktypes_internal.php";s:4:"6bf5";s:66:"classes/linktypes/class.tx_linkvalidator_linktypes_linkhandler.php";s:4:"cef8";s:55:"classes/tasks/class.tx_linkvalidator_tasks_validate.php";s:4:"d684";s:78:"classes/tasks/class.tx_linkvalidator_tasks_validateadditionalfieldprovider.php";s:4:"12af";s:14:"doc/manual.sxw";s:4:"6694";s:14:"doc/manual.txt";s:4:"d46d";s:44:"modfuncreport/class.tx_linkvalidator_modfuncreport.php";s:4:"cb5b";s:22:"modfuncreport/locallang.xml";s:4:"2319";s:26:"modfuncreport/locallang_csh.xml";s:4:"efa4";s:26:"modfuncreport/locallang_mod.xml";s:4:"e370";s:26:"modfuncreport/mod_template.html";s:4:"7ed2";s:21:"res/linkvalidator.css";s:4:"2f4e";s:21:"res/mailtemplate.html";s:4:"c425";s:20:"res/pagetsconfig.txt";s:4:"ab0f";}',
	'suggests' => array(
	),
);

?>