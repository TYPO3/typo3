<?php
/*
 * @deprecated since 6.0, the classname SC_t3lib_thumbs and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/View/ThumbnailView.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/View/ThumbnailView.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\View\\ThumbnailView');
$SOBE->init();
$SOBE->main();
?>