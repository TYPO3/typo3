<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Repository for Tx_Beuser_Domain_Model_BackendUser
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @package TYPO3
 * @subpackage beuser
 */
class Tx_Beuser_Domain_Repository_BackendUserRepository extends Tx_Extbase_Persistence_Repository {


	/**
	 * Finds Backend Users on a given list of uids
	 *
	 * @param array $uidList
	 * @return Tx_Extbase_Persistence_QueryResult<Tx_Beuser_Domain_Model_BackendUser>
	 */
	public function findByUidList($uidList) {
		$query = $this->createQuery();
		return $query->matching(
			$query->in('uid', $uidList)
		)->execute();
	}

	/**
	 * Find Backend Users matching to Demand object properties
	 *
	 * @param Tx_Beuser_Domain_Model_Demand $demand
	 * @return Tx_Extbase_Persistence_QueryResult<Tx_Beuser_Domain_Model_BackendUser>
	 */
	public function findDemanded(Tx_Beuser_Domain_Model_Demand $demand) {
		$constraints = array();
		$query = $this->createQuery();

			// Find invisible as well, but not deleted

		$constraints[] = $query->equals('deleted', 0);

		$query->setOrderings(array('username' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING));

			// Username
		if ($demand->getUsername() !== '') {
			$constraints[] = $query->like('username', '%' . $demand->getUsername() . '%');
		}

			// Only display admin users
		if ($demand->getUsertype() == Tx_Beuser_Domain_Model_Demand::USERTYPE_ADMINONLY) {
			$constraints[] = $query->equals('admin', 1);
		}

			// Only display non-admin users
		if ($demand->getUsertype() == Tx_Beuser_Domain_Model_Demand::USERTYPE_USERONLY) {
			$constraints[] = $query->equals('admin', 0);
		}

			// Only display active users
		if ($demand->getStatus() == Tx_Beuser_Domain_Model_Demand::STATUS_ACTIVE) {
			$constraints[] = $query->equals('disable', 0);
		}

			// Only display in-active users
		if ($demand->getStatus() == Tx_Beuser_Domain_Model_Demand::STATUS_INACTIVE) {
			$constraints[] = $query->logicalOr(
				$query->equals('disable', 1)
			);
		}

			// Not logged in before
		if ($demand->getLogins() == Tx_Beuser_Domain_Model_Demand::LOGIN_NONE) {
			$constraints[] = $query->equals('lastlogin', 0);
		}

			// At least one login
		if ($demand->getLogins() == Tx_Beuser_Domain_Model_Demand::LOGIN_SOME) {
			$constraints[] = $query->logicalNot($query->equals('lastlogin', 0));
		}

		$query->matching($query->logicalAnd($constraints));

		return $query->execute();
	}

	/**
	 * Find Backend Users currently online
	 *
	 * @return Tx_Extbase_Persistence_QueryResult<Tx_Beuser_Domain_Model_BackendUser>
	 */
	public function findOnline() {
		$uids = array();

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT ses_userid', 'be_sessions', '');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$uids[] = $row['ses_userid'];
		}

		$query = $this->createQuery();
		$query->matching($query->in('uid', $uids));

		return $query->execute();
	}

	/**
	 * Overwrite createQuery to don't respect enable fields
	 *
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function createQuery() {
		$query = parent::createQuery();
		$query->getQuerySettings()->setRespectEnableFields(FALSE);
		return $query;
	}
}

?>