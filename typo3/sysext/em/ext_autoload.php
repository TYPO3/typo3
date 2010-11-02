<?php
$emClassesPath = PATH_site . 'typo3/sysext/em/classes/';
$emInterfacesPath = PATH_site . 'typo3/sysext/em/interfaces/';
return array(
	'sc_mod_tools_em_index' => $emClassesPath . '../mod1/class.em_index.php',
	'em_connection_exception' => $emClassesPath . 'exception/class.em_connection_exception.php',
	'em_tasks_updateextensionlist' => $emClassesPath . 'tasks/class.em_tasks_updateextensionlist.php',
	'em_index_checkdatabaseupdateshook' => $emInterfacesPath . 'interface.em_index_checkdatabaseupdateshook.php',
);
?>