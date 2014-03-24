<?php
namespace TYPO3\CMS\Beuser\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Felix Kopp <felix-source@phorax.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

/**
 * Backend user switchback, for logoff_pre_processing hook within
 * \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication class
 *
 * @author Kasper Skårhøj (kasperYYYY@typo3.com)
 * @author Sebastian Kurfürst <sebastian@garbage-group.de>
 * @author Felix Kopp <felix-source@phorax.com>
 * @author Pascal Dürsteler <pascal@notionlab.ch>
 */
class SwitchBackUserHook {

	/**
	 * Switch backend user session.
	 *
	 * @param array $params
	 * @param AbstractUserAuthentication $authentication
	 * @see AbstractUserAuthentication
	 * @return void
	 */
	public function switchBack($params, AbstractUserAuthentication $authentication) {
		if ($this->isAHandledBackendSession($authentication)) {
			$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			$backendUserSessionRepository = $objectManager->get('TYPO3\\CMS\\Beuser\\Domain\\Repository\\BackendUserSessionRepository');
			$backendUserSessionRepository->switchBackToOriginalUser($authentication);
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($GLOBALS['BACK_PATH'] . 'backend.php');
		}
	}

	/**
	 * Check if the given authentication object is a backend session and
	 * contains all necessary information to allow switching.
	 *
	 * @param AbstractUserAuthentication $authentication
	 * @return bool
	 */
	protected function isAHandledBackendSession(AbstractUserAuthentication $authentication) {
		if (
			$authentication->session_table !== 'be_sessions'
			|| !is_array($authentication->user)
			|| !$authentication->user['uid']
			|| !$authentication->user['ses_backuserid']
		) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

}