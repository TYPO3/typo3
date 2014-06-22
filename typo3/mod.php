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
/**
 * Module Dispatch script
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
unset($MCONF);
require __DIR__ . '/init.php';
// Find module path:
$moduleName = (string)\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M');
$isDispatched = FALSE;
$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
if (!$formprotection->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('moduleToken'), 'moduleCall', $moduleName)) {
	throw new UnexpectedValueException('Invalid form/module token detected. Access Denied!', 1392409507);
}
if ($temp_path = $TBE_MODULES['_PATHS'][$moduleName]) {
	$MCONF['_'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl($moduleName);
	require $temp_path . 'conf.php';
	$BACK_PATH = '';
	require $temp_path . 'index.php';
	$isDispatched = TRUE;
} else {
	if (is_array($TBE_MODULES['_dispatcher'])) {
		foreach ($TBE_MODULES['_dispatcher'] as $dispatcherClassName) {
			$dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get($dispatcherClassName);
			if ($dispatcher->callModule($moduleName) === TRUE) {
				$isDispatched = TRUE;
				break;
			}
		}
	}
}
if ($isDispatched === FALSE) {
	throw new UnexpectedValueException('No module "' . htmlspecialchars($moduleName) . '" could be found.', 1294585070);
}
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->shutdown();
