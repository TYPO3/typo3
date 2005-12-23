<?php
define('TYPO3_MOD_PATH', 'ext/tstemplate/ts/');
$BACK_PATH='../../../';

$MLANG['default']['tabs_images']['tab'] = 'ts1.gif';
$MLANG['default']['ll_ref']='LLL:EXT:tstemplate/ts/locallang_mod.php';

$MCONF['script']='index.php';
$MCONF['access']='admin';		// If this is changed so not only admin-users can manipulate templates, there need to be done something with the constant editor that is not allowed to 'clear all cache' then!!
$MCONF['name']='web_ts';
?>