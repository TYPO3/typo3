<?php
/*
 * @deprecated since 6.0, the classname tx_rtehtmlarea_pi1 and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/rtehtmlarea/Classes/Controller/SpellCheckingController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rtehtmlarea') . 'Classes/Controller/SpellCheckingController.php';
if (TYPO3_MODE == 'FE') {
	$spellChecker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\Controller\\SpellCheckingController');
	$spellChecker->main();
}
?>