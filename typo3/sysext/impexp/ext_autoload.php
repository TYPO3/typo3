<?php
	// Register necessary class names with autoloader
$extensionPath = t3lib_extMgm::extPath('impexp');
return array(
	'tx_impexp' => $extensionPath . 'class.tx_impexp.php',
	'tx_impexp_task' => $extensionPath . 'task/class.tx_impexp_task.php',
	'tx_impexp_localpagetree' => $extensionPath . 'classes/class.tx_impexp_localpagetree.php',
);
?>