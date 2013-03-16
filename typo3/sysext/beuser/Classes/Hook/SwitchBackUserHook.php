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

/**
 * Backend user switchback, for logoff_pre_processing hook within
 * \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication class
 *
 * @author Kasper Skårhøj (kasperYYYY@typo3.com)
 * @author Sebastian Kurfürst <sebastian@garbage-group.de>
 * @author Felix Kopp <felix-source@phorax.com>
 */
class SwitchBackUserHook {

	/**
	 * Switch backend user session
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $that
	 * @see \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
	 */
	public function switchBack($params, $that) {
		// Is a backend session handled?
		if (
			$that->session_table !== 'be_sessions'
			|| !is_array($that->user)
			|| !$that->user['uid']
			|| !$that->user['ses_backuserid']
		) {
			return;
		}

		// @TODO: Move update functionality to Tx_Beuser_Domain_Repository_BackendUserSessionRepository
		$updateData = array(
			'ses_userid' => $that->user['ses_backuserid'],
			'ses_backuserid' => 0
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'be_sessions',
			'ses_id = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions') .
				' AND ses_name = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getCookieName(), 'be_sessions') .
				' AND ses_userid=' . intval($GLOBALS['BE_USER']->user['uid']), $updateData
		);

		$redirectUrl = $GLOBALS['BACK_PATH'] . 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
	}

}

?>