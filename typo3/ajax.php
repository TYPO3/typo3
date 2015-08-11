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
 * AJAX dispatcher
 * main entry point for AJAX calls in the TYPO3 Backend. Based on $TYPO3_AJAX the Bootstrap let's the request
 * handled by TYPO3\CMS\Backend\AjaxRequestHandler and AjaxController.
 * See $TYPO3_CONF_VARS['BE']['AJAX'] and the Core APIs on how to register an AJAX call in the TYPO3 Backend.
 */
call_user_func(function() {
	$classLoader = require __DIR__ . '/../vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
			'The entry point to ajax.php was moved to index.php with ajaxID given. Please use BackendUtility::getAjaxUrl(\'myAjaxKey\') to link to the AJAX Call. This script will be removed in TYPO3 CMS 8.'
		);
	});
});
