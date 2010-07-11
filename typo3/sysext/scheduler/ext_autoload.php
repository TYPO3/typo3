<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id$
 */
// TODO: document necessity of providing autoloader information
return array(
	'tx_scheduler'										=> t3lib_extMgm::extPath('scheduler', 'class.tx_scheduler.php'),
	'tx_scheduler_croncmd'								=> t3lib_extMgm::extPath('scheduler', 'class.tx_scheduler_croncmd.php'),
	'tx_scheduler_task'									=> t3lib_extMgm::extPath('scheduler', 'class.tx_scheduler_task.php'),
	'tx_scheduler_execution'							=> t3lib_extMgm::extPath('scheduler', 'class.tx_scheduler_execution.php'),
	'tx_scheduler_failedexecutionexception'				=> t3lib_extMgm::extPath('scheduler', 'class.tx_scheduler_failedexecutionexception.php'),
	'tx_scheduler_testtask'								=> t3lib_extMgm::extPath('scheduler', 'examples/class.tx_scheduler_testtask.php'),
	'tx_scheduler_testtask_additionalfieldprovider'		=> t3lib_extMgm::extPath('scheduler', 'examples/class.tx_scheduler_testtask_additionalfieldprovider.php'),
	'tx_scheduler_sleeptask'							=> t3lib_extMgm::extPath('scheduler', 'examples/class.tx_scheduler_sleeptask.php'),
	'tx_scheduler_sleeptask_additionalfieldprovider'	=> t3lib_extMgm::extPath('scheduler', 'examples/class.tx_scheduler_sleeptask_additionalfieldprovider.php')
);
?>