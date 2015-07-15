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
 * Page navigation tree for the Web module
 */
call_user_func(function() {
	$classLoader = require __DIR__ . '/vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('Usage of alt_db_navframe.php is deprecated since TYPO3 CMS 7, and will be removed in TYPO3 CMS 8');
		// Make instance if it is not an AJAX call
		if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
			$pageTreeNavigationController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\PageTreeNavigationController::class);
			$pageTreeNavigationController->initPage();
			$pageTreeNavigationController->main();
			$pageTreeNavigationController->printContent();
		}
	});
});
