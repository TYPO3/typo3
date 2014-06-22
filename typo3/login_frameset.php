<?php
/**
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
require __DIR__ . '/init.php';

// Make instance:
$loginFramesetController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\LoginFramesetController');
$loginFramesetController->main();
$loginFramesetController->printContent();
