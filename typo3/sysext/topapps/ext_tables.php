<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::addTopApp('xMOD_txtopapps_menu',t3lib_extMgm::extPath($_EXTKEY).'menu/',FALSE,array('leftAlign' => TRUE, 'simpleContainer' => TRUE));

	t3lib_extMgm::addTopApp('xMOD_txtopapps_search',t3lib_extMgm::extPath($_EXTKEY).'search/');
	t3lib_extMgm::addTopApp('xMOD_txtopapps_clock',t3lib_extMgm::extPath($_EXTKEY).'clock/');
	t3lib_extMgm::addTopApp('xMOD_txtopapps_workspaces',t3lib_extMgm::extPath($_EXTKEY).'workspaces/');
	t3lib_extMgm::addTopApp('xMOD_txtopapps_user',t3lib_extMgm::extPath($_EXTKEY).'user/');
	t3lib_extMgm::addTopApp('xMOD_txtopapps_cache',t3lib_extMgm::extPath($_EXTKEY).'cache/');
	t3lib_extMgm::addTopApp('xMOD_txtopapps_xyzcorp',t3lib_extMgm::extPath($_EXTKEY).'xyzcorp/');

	t3lib_extMgm::addTopApp('xMOD_txtopapps_shortcuts',t3lib_extMgm::extPath($_EXTKEY).'shortcut/',TRUE,array('leftAlign' => TRUE));
	t3lib_extMgm::addTopApp('xMOD_txtopapps_dashboard',t3lib_extMgm::extPath($_EXTKEY).'dashboard/',TRUE,array('leftAlign' => TRUE));
	t3lib_extMgm::addTopApp('xMOD_txtopapps_submodules',t3lib_extMgm::extPath($_EXTKEY).'submodules/',TRUE);
}
?>