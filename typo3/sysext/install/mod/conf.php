<?php

define('TYPO3_MOD_PATH', 'sysext/install/mod/');
$BACK_PATH='../../../';

$MLANG['default']['tabs_images']['tab'] = 'install.gif';
$MLANG['default']['ll_ref']='LLL:EXT:install/mod/locallang_mod.xml';

$MCONF['script']=$BACK_PATH.'install/index.php';
$MCONF['access']='admin';
$MCONF['name']='tools_install';
$MCONF['workspaces']='online';

/*
define('TYPO3_MOD_PATH', '../typo3conf/ext/install/mod/');
$BACK_PATH='../../../../typo3/';
$BACK_PATH_FROM_EXT = '../../typo3/';

$MCONF['name']='tools_install';
$MCONF['access']='admin';
$MCONF['script']=$BACK_PATH.'install/index.php';
$MCONF['workspaces']='online';

$MLANG['default']['tabs_images']['tab'] = 'install.gif';
$MLANG['default']['ll_ref']='LLL:EXT:install/mod/locallang_mod.xml';
*/
?>