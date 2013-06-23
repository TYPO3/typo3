<?php
header('HTTP/1.0 500 Internal Server Error');
unset($MCONF);
define('TYPO3_MOD_PATH', 'sysext/openid/wizard/');
$BACK_PATH = '../../../../typo3/';
require_once '../../../../typo3/init.php';

	//openid lib needs that; it throws notices
error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));

$wizard = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
	'TYPO3\\CMS\\Openid\\Wizard'
);
$wizard->main();
?>