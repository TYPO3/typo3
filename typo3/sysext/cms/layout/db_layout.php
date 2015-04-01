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
 * Module: Web>Page
 *
 * This module lets you view a page in a more Content Management like style than the ordinary record-list
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
unset($MCONF);
require __DIR__ . '/conf.php';
require $BACK_PATH . 'init.php';
$LANG->includeLLFile('EXT:cms/layout/locallang.xlf');

$BE_USER->modAccess($MCONF, 1);
// Will open up records locked by current user. It's assumed that the locking should end if this script is hit.
\TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
	'The page layout class is moved to an own module. Please use BackendUtility::getModuleUrl(\'web_layout\') to link to db_layout.php. This script will be removed with version TYPO3 CMS 8.'
);

$GLOBALS['SOBE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\PageLayoutController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->clearCache();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
