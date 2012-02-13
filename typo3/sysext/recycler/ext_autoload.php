<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = t3lib_extMgm::extPath('recycler');
return array(
	'tx_recycler_tasks_cleanertaskadditionalfields' => $extensionPath . 'classes/tasks/class.tx_recycler_tasks_cleanertaskadditionalfields.php',
	'tx_recycler_tasks_cleanertask' => $extensionPath . 'classes/tasks/class.tx_recycler_tasks_cleanertask.php',
);

?>