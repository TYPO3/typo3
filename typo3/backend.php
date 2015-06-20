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
	$classLoader = require __DIR__ . '/contrib/vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');

		// Document generation
		$GLOBALS['TYPO3backend'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\BackendController::class);
		$GLOBALS['TYPO3backend']->render();
	});
});
