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
 * Model for backend user
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @package TYPO3
 * @subpackage beuser
 */
class Tx_Beuser_Domain_Model_BackendUser extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string
	 * @validate notEmpty
	 */
	protected $username = '';

	/**
	 * Is user an admin?
	 * @var boolean
	 */
	protected $admin = FALSE;

	/**
	 * @var boolean
	 */
	protected $disable;

	/**
	 * @var DateTime
	 */
	protected $starttime = 0;

	/**
	 * @var DateTime
	 */
	protected $endtime = 0;

	/**
	 * @var string
	 */
	protected $email = '';

	/**
	 * Comma separated list of uids in multi-select
	 * Might retreive the labels from TCA/DataMapper
	 *
	 * @var string
	 */
	protected $allowedLanguages = '';

	/**
	 * @var string
	 */
	protected $realName = '';

	/**
	 * @var DateTime
	 */
	protected $lastlogin;

	/**
	 * @var string
	 */
	protected $dbMountpoints = '';

	/**
	 * @var string
	 */
	protected $fileMountpoints = '';


	/**
	 * @param boolean $admin
	 */
	public function setAdmin($admin) {
		$this->admin = $admin;
	}

	/**
	 * @return boolean
	 */
	public function getAdmin() {
		return $this->admin;
	}

	/**
	 * @param boolean $disable
	 */
	public function setDisable($disable) {
		$this->disable = $disable;
	}

	/**
	 * @return boolean
	 */
	public function getDisable() {
		return $this->disable;
	}

	/**
	 * @param \DateTime $lastlogin
	 */
	public function setLastlogin($lastlogin) {
		$this->lastlogin = $lastlogin;
	}

	/**
	 * @return \DateTime
	 */
	public function getLastlogin() {
		return $this->lastlogin;
	}

	/**
	 * @param string $realName
	 */
	public function setRealName($realName) {
		$this->realName = $realName;
	}

	/**
	 * @return string
	 */
	public function getRealName() {
		return $this->realName;
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * @param \DateTime $starttime
	 */
	public function setStarttime($starttime) {
		$this->starttime = $starttime;
	}

	/**
	 * @return \DateTime
	 */
	public function getStarttime() {
		return $this->starttime;
	}

	/**
	 * @param \DateTime $endtime
	 */
	public function setEndtime($endtime) {
		$this->endtime = $endtime;
	}

	/**
	 * @return \DateTime
	 */
	public function getEndtime() {
		return $this->endtime;
	}

	/**
	 * @param string $allowedLanguages
	 */
	public function setAllowedLanguages($allowedLanguages) {
		$this->allowedLanguages= $allowedLanguages;
	}

	/**
	 * @return string
	 */
	public function getAllowedLanguages() {
		return $this->allowedLanguages;
	}

	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param string
	 */
	public function setDbMountpoints($dbMountpoints) {
		$this->dbMountpoints = $dbMountpoints;
	}

	/**
	 * @return string
	 */
	public function getDbMountpoints() {
		return $this->dbMountpoints;
	}

	/**
	 * @param string $fileMountpoints
	 */
	public function setFileMountpoints($fileMountpoints) {
		$this->fileMountpoints = $fileMountpoints;
	}

	/**
	 * @return string
	 */
	public function getFileMountpoints() {
		return $this->fileMountpoints;
	}

	/**
	 * Check if user is active, not disabled
	 *
	 * @return boolean
	 */
	public function isActive() {
		$now = new DateTime('now');

		if ($this->getDisable())
			return FALSE;

		return (!$this->starttime && !$this->endtime) ||
				($this->starttime <= $now && (!$this->endtime || $this->endtime > $now));
	}

	/**
	 * @param boolean $disableIpLock
	 */
	public function setDisableIpLock($disableIpLock) {
		$this->disableIpLock = $disableIpLock;
	}

	/**
	 * @return boolean
	 */
	public function getDisableIpLock() {
		return $this->disableIpLock;
	}

}

?>