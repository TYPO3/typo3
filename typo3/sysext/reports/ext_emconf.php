<?php

########################################################################
# Extension Manager/Repository config file for ext "reports".
#
# Auto generated 22-01-2011 01:12
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'System Reports',
	'description' => 'The system reports module groups several system reports.',
	'category' => 'module',
	'author' => 'Ingo Renner',
	'author_email' => 'ingo@typo3.org',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.1.0',
	'doNotLoadInFE' => 1,
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:23:{s:9:"ChangeLog";s:4:"3b22";s:16:"ext_autoload.php";s:4:"9ec3";s:12:"ext_icon.gif";s:4:"691d";s:17:"ext_localconf.php";s:4:"02ec";s:14:"ext_tables.php";s:4:"a108";s:42:"interfaces/interface.tx_reports_report.php";s:4:"bc72";s:50:"interfaces/interface.tx_reports_statusprovider.php";s:4:"2d8b";s:12:"mod/conf.php";s:4:"7fd9";s:13:"mod/index.php";s:4:"804d";s:17:"mod/locallang.xml";s:4:"7782";s:18:"mod/mod_styles.css";s:4:"905d";s:21:"mod/mod_template.html";s:4:"ff97";s:18:"mod/moduleicon.gif";s:4:"2ac9";s:43:"reports/class.tx_reports_reports_status.php";s:4:"e7d2";s:21:"reports/locallang.xml";s:4:"8f45";s:70:"reports/status/class.tx_reports_reports_status_configurationstatus.php";s:4:"cf16";s:65:"reports/status/class.tx_reports_reports_status_securitystatus.php";s:4:"48b4";s:57:"reports/status/class.tx_reports_reports_status_status.php";s:4:"d3dc";s:63:"reports/status/class.tx_reports_reports_status_systemstatus.php";s:4:"35a5";s:62:"reports/status/class.tx_reports_reports_status_typo3status.php";s:4:"99f8";s:78:"reports/status/class.tx_reports_reports_status_warningmessagepostprocessor.php";s:4:"f71c";s:55:"tasks/class.tx_reports_tasks_systemstatusupdatetask.php";s:4:"5aa8";s:77:"tasks/class.tx_reports_tasks_systemstatusupdatetasknotificationemailfield.php";s:4:"5e30";}',
	'suggests' => array(
	),
);

?>