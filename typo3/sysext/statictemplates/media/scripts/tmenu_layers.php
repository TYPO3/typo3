<?php
/*
 * @deprecated since 6.0, the classname tslib_tmenu_layers and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/frontend/Classes/ContentObject/Menu/TextMenuLayers.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('frontend') . 'Classes/ContentObject/Menu/TextMenuLayers.php';
// FULL DUPLICATE FROM gmenu_layers END:
$GLOBALS['TSFE']->tmpl->menuclasses .= ',tmenu_layers';
?>