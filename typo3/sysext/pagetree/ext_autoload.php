<?php

$extensionPath = t3lib_extMgm::extPath('pagetree');
return array(
	'tx_pagetree_extdirect_tree' => $extensionPath . 'classes/extdirect/class.tx_pagetree_extdirect_tree.php',
	'tx_pagetree_extdirect_commands' => $extensionPath . 'classes/extdirect/class.tx_pagetree_extdirect_commands.php',
	'tx_pagetree_extdirect_contextmenu' => $extensionPath . 'classes/extdirect/class.tx_pagetree_extdirect_contextmenu.php',
	'tx_pagetree_dataprovider' => $extensionPath . 'classes/class.tx_pagetree_dataprovider.php',
	'tx_pagetree_node' => $extensionPath . 'classes/class.tx_pagetree_node.php',
	't3lib_tree_extdirect_node' => $extensionPath . 'classes/class.t3lib_tree_extdirect_node.php',
	'tx_pagetree_nodecollection' => $extensionPath . 'classes/class.tx_pagetree_nodecollection.php',
	'tx_pagetree_commands' => $extensionPath . 'classes/class.tx_pagetree_commands.php',
	'tx_pagetree_contextmenu_action' => $extensionPath . 'classes/contextmenu/class.tx_pagetree_contextmenu_action.php',
	'tx_pagetree_contextmenu_dataprovider' => $extensionPath . 'classes/contextmenu/class.tx_pagetree_contextmenu_dataprovider.php',
	'tx_pagetree_indicator' => $extensionPath . 'classes/class.tx_pagetree_indicator.php',
	'tx_pagetree_indicatorprovider' => $extensionPath . 'classes/interface.tx_pagetree_indicatorprovider.php',
);

?>