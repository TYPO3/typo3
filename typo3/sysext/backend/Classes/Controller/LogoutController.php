<?php
namespace TYPO3\CMS\Backend\Controller;

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
	 * @todo Define visibility
	 */
	public function logout() {
		// Logout written to log
		$GLOBALS['BE_USER']->writelog(255, 2, 0, 1, 'User %s logged out from TYPO3 Backend', array($GLOBALS['BE_USER']->user['username']));
		\t3lib_formProtection_Factory::get()->removeSessionTokenFromRegistry();
		$GLOBALS['BE_USER']->logoff();
		$redirect = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('redirect'));
		$redirectUrl = $redirect ? $redirect : 'index.php';
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
	}

}


?>