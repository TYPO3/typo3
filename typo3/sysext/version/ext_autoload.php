<?php
/*
 * Register necessary classes with autoloader
 *
 * $Id: ext_autoload.php 6536 2009-11-25 14:07:18Z stucki $
 */
return array(
	'tx_version_tasks_autopublish' => t3lib_extMgm::extPath('version', 'tasks/class.tx_version_tasks_autopublish.php'),
	'tx_version_gui' => t3lib_extMgm::extPath('version', 'class.tx_version_gui.php')
);
?>
