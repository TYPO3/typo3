<?php
/*
 * @deprecated since 6.0, the classname SC_mod_user_setup_index and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/setup/Classes/Controller/SetupModuleController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('setup') . 'Classes/Controller/SetupModuleController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Setup\\Controller\\SetupModuleController');
$SOBE->simulateUser();
$SOBE->storeIncomingData();
// These includes MUST be afterwards the settings are saved...!
$LANG->includeLLFile('EXT:setup/mod/locallang.xml');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>