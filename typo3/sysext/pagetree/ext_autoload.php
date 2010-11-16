<?php

$extensionPath = t3lib_extMgm::extPath('pagetree');
return array(
	'tx_pagetree_abstracttree' => $extensionPath . 'classes/class.tx_pagetree_abstracttree.php',
	'tx_pagetree_pagetree' => $extensionPath . 'classes/class.tx_pagetree_pagetree.php',
	'tx_contextmenu_contextmenu' => $extensionPath . 'classes/class.tx_contextmenu_contextmenu.php',
	'tx_pagetree_dataprovider_pagetree' => $extensionPath . 'extdirect/dataproviderclass.tx_pagetree_dataprovider_pagetree.php',
	'tx_pagetree_dataprovider_abstracttree' => $extensionPath . 'extdirect/dataprovider/class.tx_pagetree_dataprovider_abstracttree.php',
);

?>