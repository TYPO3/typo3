<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
require_once 'init.php';
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');
/*
 * @deprecated since 6.0, the classname TYPO3backend and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Controller/BackendController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Controller/BackendController.php';
// Document generation
$TYPO3backend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\BackendController');
// Include extensions which may add css, javascript or toolbar items
if (is_array($GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'])) {
	foreach ($GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'] as $additionalBackendItem) {
		include_once $additionalBackendItem;
	}
}
// Process ExtJS module js and css
if (is_array($GLOBALS['TBE_MODULES']['_configuration'])) {
	foreach ($GLOBALS['TBE_MODULES']['_configuration'] as $moduleConfig) {
		if (is_array($moduleConfig['cssFiles'])) {
			foreach ($moduleConfig['cssFiles'] as $cssFileName => $cssFile) {
				$files = array(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($cssFile));
				$files = \TYPO3\CMS\Core\Utility\GeneralUtility::removePrefixPathFromList($files, PATH_site);
				$TYPO3backend->addCssFile($cssFileName, '../' . $files[0]);
			}
		}
		if (is_array($moduleConfig['jsFiles'])) {
			foreach ($moduleConfig['jsFiles'] as $jsFile) {
				$files = array(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($jsFile));
				$files = \TYPO3\CMS\Core\Utility\GeneralUtility::removePrefixPathFromList($files, PATH_site);
				$TYPO3backend->addJavascriptFile('../' . $files[0]);
			}
		}
	}
}
$TYPO3backend->render();
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->shutdown();
?>