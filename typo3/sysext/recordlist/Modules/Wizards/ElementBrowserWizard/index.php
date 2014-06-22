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
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 */
$elementBrowserController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\Controller\\ElementBrowserController');
$elementBrowserController->main();
$elementBrowserController->printContent();
