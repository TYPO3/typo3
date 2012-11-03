<?php
// Register necessary class names with autoloader
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('taskcenter');
return array(
	'tx_taskcenter_task' => $extensionPath . 'interfaces/interface.tx_taskcenter_task.php'
);
?>