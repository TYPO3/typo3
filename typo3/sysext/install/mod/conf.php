<?php
define('TYPO3_MOD_PATH', 'sysext/install/mod/');
$BACK_PATH='../../../';

$MLANG['default']['tabs_images']['tab'] = 'install.gif';
$MLANG['default']['ll_ref']='LLL:EXT:install/mod/locallang_mod.php';

$MCONF['script']=$BACK_PATH.'install/index.php';
$MCONF['access']='admin';
$MCONF['name']='tools_install';
$MCONF['workspaces']='online';
?>