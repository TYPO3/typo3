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
 * TCE gateway (TYPO3 Core Engine) for database handling
 * This script is a gateway for POST forms to \TYPO3\CMS\Core\DataHandling\DataHandler
 * that manipulates all information in the database!!
 * For syntax and API information, see the document 'TYPO3 Core APIs'
 */
call_user_func(function() {
	$classLoader = require __DIR__ . '/../vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'The entry point to data handling via DataHandler was moved to an own module. Please use BackendUtility::getModuleUrl(\'tce_db\') to link to tce_db.php / DataHandler. This script will be removed in TYPO3 CMS 8.'
		);

		$simpleDataHandlerController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\SimpleDataHandlerController::class);

		$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
		if ($formprotection->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('formToken'), 'tceAction')) {
			$simpleDataHandlerController->initClipboard();
			$simpleDataHandlerController->main();
		}
		$simpleDataHandlerController->finish();
	});
});
