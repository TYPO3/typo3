<?php
namespace TYPO3\CMS\Beuser\Domain\Repository;

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
 * Repository for \TYPO3\CMS\Extbase\Domain\Model\BackendUser
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @author Pascal Dürsteler <pascal@notionlab.ch>
 */
class BackendUserSessionRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * Find Sessions for specific BackendUser
	 * Delivers an Array, not an ObjectStorage!
	 *
	 * @param \TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser
	 * @return array
	 */
	public function findByBackendUser(\TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser) {
		$sessions = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'ses_id AS id, ses_iplock AS ip, ses_tstamp AS timestamp',
			'be_sessions',
			'ses_userid = ' . (int)$backendUser->getUid(),
			'',
			'ses_tstamp ASC'
		);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$sessions[] = array(
				'id' => $row['id'],
				'ip' => $row['ip'],
				'timestamp' => $row['timestamp']
			);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $sessions;
	}

	/**
	 * Update current session to move back to the original user.
	 *
	 * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $authentication
	 * @return void
	 */
	public function switchBackToOriginalUser(\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $authentication) {
		$updateData = array(
			'ses_userid' => $authentication->user['ses_backuserid'],
			'ses_backuserid' => 0,
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'be_sessions',
			'ses_id = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions') .
				' AND ses_name = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getCookieName(), 'be_sessions') .
				' AND ses_userid=' . (int)$GLOBALS['BE_USER']->user['uid'], $updateData
		);
	}

}
