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
 * Module: Web>Info
 * Presents various page related information from extensions
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Info\\Controller\\InfoModuleController');
$SOBE->init();
// Include files?
// @deprecated since 6.2 (see ExtensionManagementUtility::insertModuleFunction)
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
// Checking for first level external objects
$SOBE->checkExtObj();
// Repeat Include files! - if any files has been added by second-level extensions
// @deprecated since 6.2 (see ExtensionManagementUtility::insertModuleFunction)
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
// Checking second level external objects
$SOBE->checkSubExtObj();
$SOBE->main();
$SOBE->printContent();
