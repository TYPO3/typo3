<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
require_once 'init.php';
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');

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
