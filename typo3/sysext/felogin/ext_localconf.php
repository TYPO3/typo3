<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	//replace old Login
$pluginContent = trim('
plugin.tx_felogin_pi1 = USER_INT
plugin.tx_felogin_pi1 {
  includeLibs = EXT:felogin/pi1/class.tx_felogin_pi1.php
  userFunc = tx_felogin_pi1->main
}
');
	t3lib_extMgm::addTypoScript($_EXTKEY,'setup','
# Setting '.$_EXTKEY.' plugin TypoScript
'.$pluginContent);

$addLine = trim('
tt_content.login = COA
tt_content.login {
	10 = < lib.stdheader
	20 >
	20 = < plugin.tx_felogin_pi1
}
');

t3lib_extMgm::addTypoScript($_EXTKEY,'setup','# Setting '.$_EXTKEY.' plugin TypoScript'.$addLine.'',43);


//activate support for kb_md5fepw
if (t3lib_extMgm::isLoaded('kb_md5fepw') && (TYPO3_MODE == 'FE')) {
	$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'][] = 'tx_kbmd5fepw_newloginbox->loginFormOnSubmit';
	require_once(t3lib_extMgm::extPath('kb_md5fepw').'pi1/class.tx_kbmd5fepw_newloginbox.php');
}

?>