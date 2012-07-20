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
 * Demand filter for listings
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @package TYPO3
 * @subpackage beuser
 */
class Tx_Beuser_Domain_Model_Demand extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var integer
	 */
	const ALL = 0;

	/**
	 * @var integer
	 */
	const USERTYPE_ADMINONLY = 1;
	/**
	 * @var integer
	 */
	const USERTYPE_USERONLY = 2;

	/**
	 * @var integer
	 */
	const STATUS_ACTIVE = 1;
	/**
	 * @var integer
	 */
	const STATUS_INACTIVE = 2;

	/**
	 * @var integer
	 */
	const LOGIN_SOME = 1;
	/**
	 * @var integer
	 */
	const LOGIN_NONE = 2;

	/**
	 * @var string
	 */
	protected $userName = '';

	/**
	 * @var integer
	 */
	protected $userType = self::ALL;

	/**
	 * @var integer
	 */
	protected $status = self::ALL;

	/**
	 * @var integer
	 */
	protected $logins = 0;

	/**
	 * @var Tx_Beuser_Domain_Model_BackendUserGroup
	 */
	protected $backendUserGroup;

	/**
	 * @param string $userName
	 * @return void
	 */
	public function setUserName($userName) {
		$this->userName = $userName;
	}

	/**
	 * @return string
	 */
	public function getUserName() {
		return $this->userName;
	}

	/**
	 * @param integer $userType
	 * @return void
	 */
	public function setUserType($userType) {
		$this->userType = $userType;
	}

	/**
	 * @return integer
	 */
	public function getUserType() {
		return $this->userType;
	}

	/**
	 * @param integer $status
	 * @return void
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

	/**
	 * @return integer
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param integer $logins
	 * @return void
	 */
	public function setLogins($logins) {
		$this->logins = $logins;
	}

	/**
	 * @return integer
	 */
	public function getLogins() {
		return $this->logins;
	}

	/**
	 * @param \Tx_Beuser_Domain_Model_BackendUserGroup $backendUserGroup
	 */
	public function setBackendUserGroup($backendUserGroup) {
		$this->backendUserGroup = $backendUserGroup;
	}

	/**
	 * @return \Tx_Beuser_Domain_Model_BackendUserGroup
	 */
	public function getBackendUserGroup() {
		return $this->backendUserGroup;
	}

}

?>