<?php
/*
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
$formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
if (!$formProtection->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('moduleToken'), 'moduleCall', $moduleName)) {
	throw new UnexpectedValueException('Invalid form/module token detected. Access Denied!', 1392409507);
}
if ($temp_path = $GLOBALS['TBE_MODULES']['_PATHS'][$moduleName]) {
	$GLOBALS['MCONF']['_'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl($moduleName);
	if (file_exists($temp_path . 'conf.php')) {
		require $temp_path . 'conf.php';
		$moduleConfiguration = $GLOBALS['MCONF'];
	} else {
		$moduleConfiguration = $GLOBALS['TBE_MODULES']['_configuration'][$moduleName];
	}
	if (!empty($moduleConfiguration['access'])) {
		$GLOBALS['BE_USER']->modAccess($moduleConfiguration, TRUE);
	}

	$BACK_PATH = '';
	require $temp_path . 'index.php';
	$isDispatched = TRUE;
} else {
	if (is_array($GLOBALS['TBE_MODULES']['_dispatcher'])) {
		foreach ($GLOBALS['TBE_MODULES']['_dispatcher'] as $dispatcherClassName) {
			$dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)->get($dispatcherClassName);
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
