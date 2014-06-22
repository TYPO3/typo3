<?php
namespace TYPO3\CMS\Backend\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for logging a user out.
 * Does not display any content, just calls the logout-function for the current user and then makes a redirect.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class LogoutController {

	/**
	 * Performs the logout processing
	 *
	 * @return void
	 */
	public function logout() {
		// Logout written to log
		$GLOBALS['BE_USER']->writelog(255, 2, 0, 1, 'User %s logged out from TYPO3 Backend', array($GLOBALS['BE_USER']->user['username']));
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->removeSessionTokenFromRegistry();
		$GLOBALS['BE_USER']->logoff();
		$redirect = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect'));
		$redirectUrl = $redirect ? $redirect : 'index.php';
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
	}

}
