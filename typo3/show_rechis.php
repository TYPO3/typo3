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
 * Shows information about a database or file item
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
require __DIR__ . '/init.php';

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
	'The history of a record was moved to a separate module. Please use BackendUtility::getModuleUrl(\'record_history\') to link to this script.
	This script will be removed two versions after 6.2.'
);

$elementHistoryController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementHistoryController');
$elementHistoryController->main();
$elementHistoryController->printContent();
