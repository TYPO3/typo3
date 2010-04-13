<?php
/*
 * Register necessary class names with autoloader
 *
 */
$extensionPath = t3lib_extMgm::extPath('sys_action');
return array(
	'tx_sysaction_task' => $extensionPath . 'task/class.tx_sysaction_task.php',
);
?>