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
 * This is the frameset to the file/record browser window
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
call_user_func(function() {
	$classLoader = require __DIR__ . '/contrib/vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'The entry point to the file/record browser window was moved to an own module. Please use BackendUtility::getModuleUrl(\'browser\') to link to browser.php. This script will be removed in TYPO3 CMS 8.'
		);

		$elementBrowserFramesetController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\Controller\ElementBrowserFramesetController::class);
		$elementBrowserFramesetController->main();
		$elementBrowserFramesetController->printContent();
	});
});
