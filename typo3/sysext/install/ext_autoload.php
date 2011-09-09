<?php
/*
 * Register necessary class names with autoloader
 */

$extPath = t3lib_extMgm::extPath('install');
return array(
	'tx_install_report_installstatus' => $extPath . 'report/class.tx_install_report_installstatus.php',
	'tx_install_service_basicservice' => $extPath . 'Classes/Service/BasicService.php',
	'tx_install_updates_base' => $extPath . 'Classes/Updates/Base.php',
	'tx_install_interfaces_checkthedatabasehook' => $extPath . 'Classes/Interfaces/CheckTheDatabaseHook.php'
);

?>