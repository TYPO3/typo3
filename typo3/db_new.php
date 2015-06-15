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
 * New database item menu
 *
 * This script lets users choose a new database element to create.
 * Includes a wizard mode for visually pointing out the position of new pages
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
define('TYPO3_MODE', 'BE');

require __DIR__ . '/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->run('typo3/');

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
	'The entry point to create a new database entry was moved to an own module. Please use BackendUtility::getModuleUrl(\'db_new\') to link to db_new.php. This script will be removed in TYPO3 CMS 8.'
);

$newRecordController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\NewRecordController::class);
$newRecordController->main();
$newRecordController->printContent();
