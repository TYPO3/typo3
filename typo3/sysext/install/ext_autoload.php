<?php
/*
 * Register necessary class names with autoloader
 */
$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install');
return array(
	'tx_install_report_installstatus' => $extPath . 'report/class.tx_install_report_installstatus.php',
	'tx_install_service_basicservice' => $extPath . 'Classes/Service/BasicService.php',
	'tx_install_updates_file_ttcontentupgradewizard' => $extPath . 'Classes/Updates/File/TtContentUpgradeWizard.php',
	'tx_install_updates_file_ttcontentuploadsupgradewizard' => $extPath . 'Classes/Updates/File/TtContentUploadsUpgradeWizard.php',
	'tx_install_updates_base' => $extPath . 'Classes/Updates/Base.php',
	'tx_install_interfaces_checkthedatabasehook' => $extPath . 'Classes/Interfaces/CheckTheDatabaseHook.php'
);
?>