<?php

########################################################################
# Extension Manager/Repository config file for ext: "scheduler"
#
# Auto generated 03-08-2009 22:49
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'_md5_values_when_last_written' => 'a:32:{s:9:"ChangeLog";s:4:"0f49";s:10:"README.txt";s:4:"022a";s:22:"class.tx_scheduler.php";s:4:"6605";s:30:"class.tx_scheduler_croncmd.php";s:4:"5ba8";s:32:"class.tx_scheduler_execution.php";s:4:"61ba";s:27:"class.tx_scheduler_task.php";s:4:"aa46";s:16:"ext_autoload.php";s:4:"e4c9";s:21:"ext_conf_template.txt";s:4:"07a2";s:12:"ext_icon.gif";s:4:"42ba";s:17:"ext_localconf.php";s:4:"c5e3";s:14:"ext_tables.php";s:4:"2ba4";s:14:"ext_tables.sql";s:4:"2185";s:13:"locallang.xml";s:4:"89d7";s:30:"cli/scheduler_cli_dispatch.php";s:4:"4e39";s:14:"doc/manual.sxw";s:4:"5629";s:41:"examples/class.tx_scheduler_sleeptask.php";s:4:"ee07";s:46:"examples/class.tx_scheduler_sleeptask_hook.php";s:4:"8738";s:40:"examples/class.tx_scheduler_testtask.php";s:4:"0030";s:45:"examples/class.tx_scheduler_testtask_hook.php";s:4:"ca4c";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"ab0d";s:14:"mod1/index.php";s:4:"abe7";s:18:"mod1/locallang.xml";s:4:"e2eb";s:32:"mod1/locallang_csh_scheduler.xml";s:4:"668a";s:22:"mod1/locallang_mod.xml";s:4:"76ff";s:22:"mod1/mod_template.html";s:4:"7150";s:19:"mod1/moduleicon.gif";s:4:"42ba";s:23:"res/tx_scheduler_be.css";s:4:"6daa";s:22:"res/tx_scheduler_be.js";s:4:"2238";s:17:"res/gfx/error.png";s:4:"e4dd";s:14:"res/gfx/ok.png";s:4:"8bfe";s:19:"res/gfx/warning.png";s:4:"c847";}',
	'suggests' => array(
	),
);

?>