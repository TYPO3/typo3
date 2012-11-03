<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('scheduler');
return array(
	'tx_scheduler' => $extensionPath . 'class.tx_scheduler.php',
	'tx_scheduler_croncmd' => $extensionPath . 'class.tx_scheduler_croncmd.php',
	'tx_scheduler_croncmd_normalize' => $extensionPath . 'class.tx_scheduler_croncmd_normalize.php',
	'tx_scheduler_execution' => $extensionPath . 'class.tx_scheduler_execution.php',
	'tx_scheduler_failedexecutionexception' => $extensionPath . 'class.tx_scheduler_failedexecutionexception.php',
	'tx_scheduler_task' => $extensionPath . 'class.tx_scheduler_task.php',
	'tx_scheduler_sleeptask' => $extensionPath . 'examples/class.tx_scheduler_sleeptask.php',
	'tx_scheduler_sleeptask_additionalfieldprovider' => $extensionPath . 'examples/class.tx_scheduler_sleeptask_additionalfieldprovider.php',
	'tx_scheduler_testtask' => $extensionPath . 'examples/class.tx_scheduler_testtask.php',
	'tx_scheduler_testtask_additionalfieldprovider' => $extensionPath . 'examples/class.tx_scheduler_testtask_additionalfieldprovider.php',
	'tx_scheduler_additionalfieldprovider' => $extensionPath . 'interfaces/interface.tx_scheduler_additionalfieldprovider.php',
	'tx_scheduler_progressprovider' => $extensionPath . 'interfaces/interface.tx_scheduler_progressprovider.php',
	'tx_scheduler_module' => $extensionPath . 'class.tx_scheduler_module.php',
	'tx_scheduler_croncmdtest' => $extensionPath . 'tests/tx_scheduler_croncmdTest.php',
	'tx_scheduler_cachingframeworkgarbagecollection' => $extensionPath . 'tasks/class.tx_scheduler_cachingframeworkgarbagecollection.php',
	'tx_scheduler_cachingframeworkgarbagecollection_additionalfieldprovider' => $extensionPath . 'tasks/class.tx_scheduler_cachingframeworkgarbagecollection_additionalfieldprovider.php',
	'tx_scheduler_fileindexing' => $extensionPath . 'tasks/class.tx_scheduler_fileindexing.php',
	'tx_scheduler_tablegarbagecollection' => $extensionPath . 'tasks/class.tx_scheduler_tablegarbagecollection.php',
	'tx_scheduler_tablegarbagecollection_additionalfieldprovider' => $extensionPath . 'tasks/class.tx_scheduler_tablegarbagecollection_additionalfieldprovider.php',
	'tx_scheduler_recyclergarbagecollection' => $extensionPath . 'tasks/class.tx_scheduler_recyclergarbagecollection.php',
	'tx_scheduler_recyclergarbagecollection_additionalfieldprovider' => $extensionPath . 'tasks/class.tx_scheduler_recyclergarbagecollection_additionalfieldprovider.php'
);
?>