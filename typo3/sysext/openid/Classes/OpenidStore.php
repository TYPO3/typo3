<?php
namespace TYPO3\CMS\Openid;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Dmitry Dulepov (dmitry.dulepov@gmail.com)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * This class is a TYPO3-specific OpenID store.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class OpenidStore extends \Auth_OpenID_OpenIDStore {

	const ASSOCIATION_TABLE_NAME = 'tx_openid_assoc_store';
	const ASSOCIATION_EXPIRATION_SAFETY_INTERVAL = 120;
	/* 2 minutes */
	const NONCE_TABLE_NAME = 'tx_openid_nonce_store';
	const NONCE_STORAGE_TIME = 864000;
	/* 10 days */
	/**
	 * Sores the association for future use
	 *
	 * @param string $serverUrl Server URL
	 * @param \Auth_OpenID_Association $association OpenID association
	 * @return void
	 */
	public function storeAssociation($serverUrl, $association) {
		/* @var $association \Auth_OpenID_Association */
		$GLOBALS['TYPO3_DB']->sql_query('START TRANSACTION');
		if ($this->doesAssociationExist($serverUrl, $association->handle)) {
			$this->updateExistingAssociation($serverUrl, $association);
		} else {
			$this->storeNewAssociation($serverUrl, $association);
		}
		$GLOBALS['TYPO3_DB']->sql_query('COMMIT');
	}

	/**
	 * Removes all expired associations.
	 *
	 * @return int A number of removed associations
	 */
	public function cleanupAssociations() {
		$where = sprintf('expires<=%d', time());
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(self::ASSOCIATION_TABLE_NAME, $where);
		return $GLOBALS['TYPO3_DB']->sql_affected_rows();
	}

	/**
	 * Obtains the association to the server
	 *
	 * @param string $serverUrl Server URL
	 * @param string $handle Association handle (optional)
	 * @return \Auth_OpenID_Association
	 */
	public function getAssociation($serverUrl, $handle = NULL) {
		$this->cleanupAssociations();
		$where = sprintf('server_url=%s AND expires>%d', $GLOBALS['TYPO3_DB']->fullQuoteStr($serverUrl, self::ASSOCIATION_TABLE_NAME), time());
		if ($handle != NULL) {
			$where .= sprintf(' AND assoc_handle=%s', $GLOBALS['TYPO3_DB']->fullQuoteStr($handle, self::ASSOCIATION_TABLE_NAME));
			$sort = '';
		} else {
			$sort = 'tstamp DESC';
		}
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, content', self::ASSOCIATION_TABLE_NAME, $where, '', $sort);
		$result = NULL;
		if (is_array($row)) {
			$result = @unserialize(base64_decode($row['content']));
			if ($result === FALSE) {
				$result = NULL;
			} else {
				$this->updateAssociationTimeStamp($row['tstamp']);
			}
		}
		return $result;
	}

	/**
	 * Removes the association
	 *
	 * @param string $serverUrl Server URL
	 * @param string $handle Association handle (optional)
	 * @return boolean TRUE if the association existed
	 * @todo Define visibility
	 */
	public function removeAssociation($serverUrl, $handle) {
		$where = sprintf('server_url=%s AND assoc_handle=%s', $GLOBALS['TYPO3_DB']->fullQuoteStr($serverUrl, self::ASSOCIATION_TABLE_NAME), $GLOBALS['TYPO3_DB']->fullQuoteStr($handle, self::ASSOCIATION_TABLE_NAME));
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(self::ASSOCIATION_TABLE_NAME, $where);
		$deletedCount = $GLOBALS['TYPO3_DB']->sql_affected_rows();
		return $deletedCount > 0;
	}

	/**
	 * Removes old nonces
	 *
	 * @return void
	 */
	public function cleanupNonces() {
		$where = sprintf('crdate<%d', time() - self::NONCE_STORAGE_TIME);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(self::NONCE_TABLE_NAME, $where);
	}

	/**
	 * Checks if this nonce was already used
	 *
	 * @param string $serverUrl Server URL
	 * @param integer $timestamp Time stamp
	 * @param string $salt Nonce value
	 * @return boolean TRUE if nonce was not used before anc can be used now
	 */
	public function useNonce($serverUrl, $timestamp, $salt) {
		$result = FALSE;
		if (abs($timestamp - time()) < $GLOBALS['Auth_OpenID_SKEW']) {
			$values = array(
				'crdate' => time(),
				'salt' => $salt,
				'server_url' => $serverUrl,
				'tstamp' => $timestamp
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(self::NONCE_TABLE_NAME, $values);
			$affectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();
			$result = $affectedRows > 0;
		}
		return $result;
	}

	/**
	 * Resets the store by removing all data in it
	 *
	 * @return void
	 */
	public function reset() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(self::ASSOCIATION_TABLE_NAME, '1=1');
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(self::NONCE_TABLE_NAME, '1=1');
	}

	/**
	 * Checks if such association exists.
	 *
	 * @param string $serverUrl Server URL
	 * @param \Auth_OpenID_Association $association OpenID association
	 * @return boolean
	 */
	protected function doesAssociationExist($serverUrl, $association) {
		$where = sprintf('server_url=%s AND assoc_handle=%s AND expires>%d', $GLOBALS['TYPO3_DB']->fullQuoteStr($serverUrl, self::ASSOCIATION_TABLE_NAME), $GLOBALS['TYPO3_DB']->fullQuoteStr($association->handle, self::ASSOCIATION_TABLE_NAME), time());
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('COUNT(*) as assocCount', self::ASSOCIATION_TABLE_NAME, $where);
		return $row['assocCount'] > 0;
	}

	/**
	 * Updates existing association.
	 *
	 * @param string $serverUrl Server URL
	 * @param \Auth_OpenID_Association $association OpenID association
	 * @return void
	 */
	protected function updateExistingAssociation($serverUrl, \Auth_OpenID_Association $association) {
		$where = sprintf('server_url=%s AND assoc_handle=%s AND expires>%d', $GLOBALS['TYPO3_DB']->fullQuoteStr($serverUrl, self::ASSOCIATION_TABLE_NAME), $GLOBALS['TYPO3_DB']->fullQuoteStr($association->handle, self::ASSOCIATION_TABLE_NAME), time());
		$serializedAssociation = serialize($association);
		$values = array(
			'content' => base64_encode($serializedAssociation),
			'tstamp' => time()
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(self::ASSOCIATION_TABLE_NAME, $where, $values);
	}

	/**
	 * Stores new association to the database.
	 *
	 * @param string $serverUrl Server URL
	 * @param \Auth_OpenID_Association $association OpenID association
	 * @return void
	 */
	protected function storeNewAssociation($serverUrl, $association) {
		$serializedAssociation = serialize($association);
		$values = array(
			'assoc_handle' => $association->handle,
			'content' => base64_encode($serializedAssociation),
			'crdate' => $association->issued,
			'tstamp' => time(),
			'expires' => $association->issued + $association->lifetime - self::ASSOCIATION_EXPIRATION_SAFETY_INTERVAL,
			'server_url' => $serverUrl
		);
		// In the next query we can get race conditions. sha1_hash prevents many
		// asociations from being stored for one server
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(self::ASSOCIATION_TABLE_NAME, $values);
	}

	/**
	 * Updates association time stamp.
	 *
	 * @param integer $recordId Association record id in the database
	 * @return void
	 */
	protected function updateAssociationTimeStamp($recordId) {
		$where = sprintf('uid=%d', $recordId);
		$values = array(
			'tstamp' => time()
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(self::ASSOCIATION_TABLE_NAME, $where, $values);
	}

}


?>