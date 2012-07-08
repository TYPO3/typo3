<?php
	// Register necessary class names with autoloader
$extensionPath = t3lib_extMgm::extPath('beuser');

return array(
	'tx_beuser_localpagetree' => $extensionPath . 'classes/class.tx_beuser_localpagetree.php',
	'tx_beuser_printallpagetree' => $extensionPath . 'classes/class.tx_beuser_printallpagetree.php',
	'tx_beuser_printallpagetree_perms' => $extensionPath . 'classes/class.tx_beuser_printallpagetree_perms.php',
	'tx_beuser_localfoldertree' => $extensionPath . 'classes/class.tx_beuser_localfoldertree.php',
	'tx_beuser_printallfoldertree' => $extensionPath . 'classes/class.tx_beuser_printallfoldertree.php',
	'tx_beuser_local_beUserAuth' => $extensionPath . 'classes/class.tx_beuser_local_beuserauth.php',
);
?>