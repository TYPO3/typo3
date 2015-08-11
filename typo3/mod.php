<?php
namespace TYPO3\CMS\Backend;

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
 * Module entry script
 * Main entry point for all modules (wizards, backend modules, module functions etc)
 * which usually uses the BackendModuleRequestHandler
 */
call_user_func(function() {
	$classLoader = require __DIR__ . '/../vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'The entry point to mod.php was moved to index.php with "M" given. Please use BackendUtility::getModuleUrl(\'myModuleKey\') to link to a module. This script will be removed in TYPO3 CMS 8.'
		);
	});
});
