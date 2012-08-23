<?php
// Register necessary class names with autoloader
$extensionPath = \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('sv');
return array(
	'tx_sv_reports_serviceslist' => $extensionPath . 'reports/class.tx_sv_reports_serviceslist.php'
);
?>