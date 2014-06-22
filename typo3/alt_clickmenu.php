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
 * Context menu
 *
 * The script is called in the top frame of the backend typically by a click on an icon for which a context menu should appear.
 * Either this script displays the context menu horizontally in the top frame or alternatively (default in MSIE, Mozilla) it writes the output to a <div>-layer in the calling document (which then appears as a layer/context sensitive menu)
 * Writing content back into a <div>-layer is necessary if we want individualized context menus with any specific content for any specific element.
 * Context menus can appear for either database elements or files
 * The input to this script is basically the "&init" var which is divided by "|" - each part is a reference to table|uid|listframe-flag.
 *
 * If you want to integrate a context menu in your scripts, please see template::getContextMenuCode()
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
require __DIR__ . '/init.php';

$clickMenuController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\ClickMenuController');

/**
 * Include files for extra click menu options
 * @deprecated since 6.1, will be removed 2 versions later
 */
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}

$clickMenuController->main();
$clickMenuController->printContent();
