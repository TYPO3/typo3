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

call_user_func(function() {
	$classLoader = require __DIR__ . '/../vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'The entry point to show_item was moved to an own module. Please use BackendUtility::getModuleUrl(\'show_item\') to link to show_item.php. This script will be removed in TYPO3 CMS 8.'
		);

		$elementInformationController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			\TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController::class
		);
		$elementInformationController->main();
		$elementInformationController->printContent();
	});
});
