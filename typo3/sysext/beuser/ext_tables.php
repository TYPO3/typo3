<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	t3lib_extMgm::addModule('tools','beuser','top',t3lib_extMgm::extPath($_EXTKEY).'mod/');
?>