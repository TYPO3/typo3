<?php
/*
 * Register necessary class names with autoloader
 *
 */
$extensionPath = t3lib_extMgm::extPath('taskcenter');
return array (
	'tx_taskcenter_task' => $extensionPath . 'interfaces/interface.tx_taskcenter_task.php'
);
?>
