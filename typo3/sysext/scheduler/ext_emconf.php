<?php

########################################################################
# Extension Manager/Repository config file for ext "scheduler".
#
# Auto generated 30-11-2009 00:44
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Scheduler',
	'description' => 'The TYPO3 Scheduler let\'s you register tasks to happen at a specific time',
	'category' => 'misc',
	'shy' => 0,
	'version' => '1.0.0',
	'dependencies' => '',
	'conflicts' => 'gabriel',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Francois Suter',
	'author_email' => 'francois@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.3.0-0.0.0',
		),
		'conflicts' => array(
			'gabriel' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:35:{s:10:"README.txt";s:4:"022a";s:22:"class.tx_scheduler.php";s:4:"a0c7";s:30:"class.tx_scheduler_croncmd.php";s:4:"47a4";s:32:"class.tx_scheduler_execution.php";s:4:"ddbf";s:47:"class.tx_scheduler_failedexecutionexception.php";s:4:"8f9a";s:27:"class.tx_scheduler_task.php";s:4:"5201";s:16:"ext_autoload.php";s:4:"653d";s:21:"ext_conf_template.txt";s:4:"20d1";s:12:"ext_icon.gif";s:4:"b5e1";s:17:"ext_localconf.php";s:4:"51b9";s:14:"ext_tables.php";s:4:"0316";s:14:"ext_tables.sql";s:4:"462a";s:13:"locallang.xml";s:4:"89d7";s:30:"cli/scheduler_cli_dispatch.php";s:4:"cfa8";s:14:"doc/manual.sxw";s:4:"d0c4";s:41:"examples/class.tx_scheduler_sleeptask.php";s:4:"2fc1";s:65:"examples/class.tx_scheduler_sleeptask_additionalfieldprovider.php";s:4:"8579";s:40:"examples/class.tx_scheduler_testtask.php";s:4:"ada0";s:64:"examples/class.tx_scheduler_testtask_additionalfieldprovider.php";s:4:"f7e3";s:61:"interfaces/interface.tx_scheduler_additionalfieldprovider.php";s:4:"6bca";s:13:"mod1/conf.php";s:4:"ab0d";s:14:"mod1/index.php";s:4:"27a0";s:18:"mod1/locallang.xml";s:4:"cdd0";s:32:"mod1/locallang_csh_scheduler.xml";s:4:"2d16";s:22:"mod1/locallang_mod.xml";s:4:"0867";s:22:"mod1/mod_template.html";s:4:"d28f";s:19:"mod1/moduleicon.gif";s:4:"b5e1";s:23:"res/tx_scheduler_be.css";s:4:"c712";s:22:"res/tx_scheduler_be.js";s:4:"b103";s:27:"res/gfx/status_disabled.png";s:4:"f930";s:26:"res/gfx/status_failure.png";s:4:"9d47";s:23:"res/gfx/status_late.png";s:4:"8dd8";s:26:"res/gfx/status_running.png";s:4:"3dff";s:28:"res/gfx/status_scheduled.png";s:4:"fa23";s:16:"res/gfx/stop.png";s:4:"1488";}',
	'suggests' => array(
	),
);

?>