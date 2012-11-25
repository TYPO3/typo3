<?php
// Register necessary class names with autoloader
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('impexp');
return array(
	'tx_impexp' => $extensionPath . 'class.tx_impexp.php',
	'tx_impexp_task' => $extensionPath . 'task/class.tx_impexp_task.php',
	'tx_impexp_localpagetree' => $extensionPath . 'Classes/class.tx_impexp_localpagetree.php'
);
?>