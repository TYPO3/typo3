<?php

########################################################################
# Extension Manager/Repository config file for ext "scheduler".
#
# Auto generated 16-10-2012 14:07
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
	'version' => '4.7.5',
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
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
		),
		'conflicts' => array(
			'gabriel' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:47:{s:22:"class.tx_scheduler.php";s:4:"0954";s:30:"class.tx_scheduler_croncmd.php";s:4:"f9b2";s:40:"class.tx_scheduler_croncmd_normalize.php";s:4:"2f87";s:32:"class.tx_scheduler_execution.php";s:4:"beb2";s:47:"class.tx_scheduler_failedexecutionexception.php";s:4:"9a0e";s:29:"class.tx_scheduler_module.php";s:4:"f03f";s:27:"class.tx_scheduler_task.php";s:4:"f500";s:16:"ext_autoload.php";s:4:"3c8f";s:21:"ext_conf_template.txt";s:4:"9e3c";s:12:"ext_icon.gif";s:4:"b5e1";s:17:"ext_localconf.php";s:4:"5001";s:14:"ext_tables.php";s:4:"0bab";s:14:"ext_tables.sql";s:4:"462a";s:13:"locallang.xlf";s:4:"6f02";s:10:"README.txt";s:4:"022a";s:30:"cli/scheduler_cli_dispatch.php";s:4:"6b41";s:14:"doc/manual.sxw";s:4:"b3d1";s:41:"examples/class.tx_scheduler_sleeptask.php";s:4:"cc92";s:65:"examples/class.tx_scheduler_sleeptask_additionalfieldprovider.php";s:4:"508b";s:40:"examples/class.tx_scheduler_testtask.php";s:4:"9c15";s:64:"examples/class.tx_scheduler_testtask_additionalfieldprovider.php";s:4:"9bc3";s:61:"interfaces/interface.tx_scheduler_additionalfieldprovider.php";s:4:"5150";s:13:"mod1/conf.php";s:4:"ab0d";s:14:"mod1/index.php";s:4:"bbc0";s:18:"mod1/locallang.xlf";s:4:"ccbf";s:32:"mod1/locallang_csh_scheduler.xlf";s:4:"e3b4";s:22:"mod1/locallang_mod.xlf";s:4:"c4c9";s:22:"mod1/mod_template.html";s:4:"248d";s:19:"mod1/moduleicon.gif";s:4:"b5e1";s:23:"res/tx_scheduler_be.css";s:4:"c3d2";s:22:"res/tx_scheduler_be.js";s:4:"1b70";s:27:"res/gfx/status_disabled.png";s:4:"8d8c";s:26:"res/gfx/status_failure.png";s:4:"5b8c";s:23:"res/gfx/status_late.png";s:4:"4b7b";s:26:"res/gfx/status_running.png";s:4:"6bae";s:28:"res/gfx/status_scheduled.png";s:4:"f03a";s:16:"res/gfx/stop.png";s:4:"e6ec";s:62:"tasks/class.tx_scheduler_cachingframeworkgarbagecollection.php";s:4:"5153";s:86:"tasks/class.tx_scheduler_cachingframeworkgarbagecollection_additionalfieldprovider.php";s:4:"ca99";s:54:"tasks/class.tx_scheduler_recyclergarbagecollection.php";s:4:"fc78";s:78:"tasks/class.tx_scheduler_recyclergarbagecollection_additionalfieldprovider.php";s:4:"2444";s:51:"tasks/class.tx_scheduler_tablegarbagecollection.php";s:4:"0e62";s:75:"tasks/class.tx_scheduler_tablegarbagecollection_additionalfieldprovider.php";s:4:"a126";s:66:"tests/class.tx_scheduler_cachingframeworkgarbagecollectionTest.php";s:4:"a22d";s:50:"tests/class.tx_scheduler_croncmd_normalizeTest.php";s:4:"a7ce";s:40:"tests/class.tx_scheduler_croncmdTest.php";s:4:"032f";s:39:"tests/class.tx_scheduler_moduleTest.php";s:4:"6cf5";}',
	'suggests' => array(
	),
);

?>