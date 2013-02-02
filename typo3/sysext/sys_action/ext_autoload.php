<?php
// Register necessary class names with autoloader
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('sys_action');
return array(
	'tx_sysaction_list' => $extensionPath . 'task/class.tx_sysaction_list.php',
	'tx_sysaction_task' => $extensionPath . 'task/class.tx_sysaction_task.php'
);
?>