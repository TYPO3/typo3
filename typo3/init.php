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
 * TYPO3 Backend initialization
 *
 * This script was called by every backend script to set up the bootstrap.
 * The script authenticates the backend user.
 * In addition this script also initializes the database and other depending on LocalConfiguration.php
 *
 * IMPORTANT:
 * This script exits if no user is logged in!
 * If you want the script to return even if no user is logged in,
 * you must define the constant TYPO3_PROCEED_IF_NO_USER=1
 * before you include this script.
 *
 *
 * This script does the following:
 * - extracts and defines path's
 * - includes certain libraries
 * - authenticates the user
 * - load the groupdata for the user and set filemounts / webmounts
 *
 * For a detailed description of this script, the scope of constants and variables in it,
 * please refer to the document "Inside TYPO3"
 *
 * Please note that this file might be removed in the future in favor of adding these lines to all entry
 * scripts as well.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
call_user_func(function() {
	$classLoader = require __DIR__ . '/contrib/vendor/autoload.php';
	(new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('Usage of typo3/init.php is deprecated since TYPO3 CMS 7, and will be removed in TYPO3 CMS 8. Initialize the bootstrap call directly in your entry script.');
	});
});
