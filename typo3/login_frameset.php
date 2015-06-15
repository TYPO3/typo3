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
 * Login frameset
 *
 * This script generates a login-frameset used when the user must relogin.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
define('TYPO3_PROCEED_IF_NO_USER', 1);
define('TYPO3_MODE', 'BE');

require __DIR__ . '/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->run('typo3/');

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
	'Login frameset is moved to an own module. Please use BackendUtility::getModuleUrl(\'login_frameset\') to link to login_frameset.php. This script will be removed in TYPO3 CMS 8.'
);

// Make instance:
$loginFramesetController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\LoginFramesetController::class);
$loginFramesetController->main();
$loginFramesetController->printContent();
