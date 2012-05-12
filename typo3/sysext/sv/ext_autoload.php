<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = t3lib_extMgm::extPath('sv');
return array(
	'tx_sv_reports_serviceslist' => $extensionPath . 'reports/class.tx_sv_reports_serviceslist.php',
	'tx_sv_authbase' => $extensionPath . 'class.tx_sv_authbase.php',
	'tx_sv_auth' => $extensionPath . 'class.tx_sv_auth.php',
	'tx_sv_loginformhook' => $extensionPath . 'class.tx_sv_loginformhook.php',
);
?>