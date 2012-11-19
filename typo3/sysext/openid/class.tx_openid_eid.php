<?php
/*
 * @deprecated since 6.0, the classname tx_openid_eID and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/openid/Classes/OpenidEid.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('openid') . 'Classes/OpenidEid.php';
$module = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Openid\\OpenidEid');
/* @var tx_openid_eID $module */
$module->main();
?>