<?php
/*
 * @deprecated since 6.0, the classname tx_install_ajax and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/install/Classes/EidHandler.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Classes/EidHandler.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\EidHandler');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>