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
 * Folder tree in the File main module.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
define('TYPO3_MODE', 'BE');

require __DIR__ . '/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->run('typo3/');

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('Usage of alt_file_navframe.php is deprecated since TYPO3 CMS 7, and will be removed in TYPO3 CMS 8');

// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$fileSystemNavigationFrameController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController::class);
	$fileSystemNavigationFrameController->initPage();
	$fileSystemNavigationFrameController->main();
	$fileSystemNavigationFrameController->printContent();
}
