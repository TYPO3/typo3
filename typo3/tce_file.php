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
 * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
 * This script serves as the fileadministration part of the TYPO3 Core Engine.
 * Basically it includes two libraries which are used to manipulate files on the server.
 *
 * For syntax and API information, see the document 'TYPO3 Core APIs'
 */
call_user_func(function() {
	$classLoader = require __DIR__ . '/../vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'File handling entry point was moved an own module. Please use BackendUtility::getModuleUrl(\'tce_file\') to link to tce_file.php. This script will be removed in TYPO3 CMS 8.'
		);

		$fileController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\File\FileController::class);

		$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
		if ($formprotection->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('formToken'), 'tceAction')) {
			$fileController->main();
		}

		$fileController->finish();
	});
});
