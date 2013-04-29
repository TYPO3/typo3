<?php
namespace TYPO3\CMS\Extbase\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This model represents a back-end user.
 *
 * @api
 */
class BackendUser extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 * @validate notEmpty
	 */
	protected $userName = '';

	/**
	 * @var boolean
	 */
	protected $isAdministrator = FALSE;

	/**
	 * @var boolean
	 */
	protected $isDisabled = FALSE;

	/**
	 * @var \DateTime|NULL
	 */
	protected $startDateAndTime = NULL;

	/**
	 * @var \DateTime|NULL
	 */
	protected $endDateAndTime = NULL;

	/**
	 * @var string
	 */
	protected $email = '';

	/**
	 * @var string
	 */
	protected $realName = '';

	/**
	 * @var \DateTime|NULL
	 */
	protected $lastLoginDateAndTime;

	/**
	 * @var boolean
	 */
	protected $ipLockIsDisabled = FALSE;

	/**
	 * Gets the user name.
	 *
	 * @return string the user name, will not be empty
	 */
	public function getUserName() {
		return $this->userName;
	}

	/**
	 * Sets the user name.
	 *
	 * @param string $userName the user name to set, must not be empty
	 * @return void
	 */
	public function setUserName($userName) {
		$this->userName = $userName;
	}

	/**
	 * Checks whether this user is an administrator.
	 *
	 * @return boolean whether this user is an administrator
	 */
	public function getIsAdministrator() {
		return $this->isAdministrator;
	}

	/**
	 * Sets whether this user should be an administrator.
	 *
	 * @param boolean $isAdministrator whether this user should be an administrator
	 * @return void
	 */
	public function setIsAdministrator($isAdministrator) {
		$this->isAdministrator = $isAdministrator;
	}

	/**
	 * Checks whether this user is disabled.
	 *
	 * @return boolean whether this user is disabled
	 */
	public function getIsDisabled() {
		return $this->isDisabled;
	}

	/**
	 * Sets whether this user is disabled.
	 *
	 * @param boolean $isDisabled whether this user is disabled
	 * @return void
	 */
	public function setIsDisabled($isDisabled) {
		$this->isDisabled = $isDisabled;
	}

	/**
	 * Returns the point in time from which this user is enabled.
	 *
	 * @return \DateTime|NULL the start date and time
	 */
	public function getStartDateAndTime() {
		return $this->startDateAndTime;
	}

	/**
	 * Sets the point in time from which this user is enabled.
	 *
	 * @param \DateTime|NULL $dateAndTime the start date and time
	 * @return void
	 */
	public function setStartDateAndTime(\DateTime $dateAndTime = NULL) {
		$this->startDateAndTime = $dateAndTime;
	}

	/**
	 * Returns the point in time before which this user is enabled.
	 *
	 * @return \DateTime|NULL the end date and time
	 */
	public function getEndDateAndTime() {
		return $this->endDateAndTime;
	}

	/**
	 * Sets the point in time before which this user is enabled.
	 *
	 * @param \DateTime|NULL $dateAndTime the end date and time
	 * @return void
	 */
	public function setEndDateAndTime(\DateTime $dateAndTime = NULL) {
		$this->endDateAndTime = $dateAndTime;
	}

	/**
	 * Gets the e-mail address of this user.
	 *
	 * @return string the e-mail address, might be empty
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Sets the e-mail address of this user.
	 *
	 * @param string $email the e-mail address, may be empty
	 * @return void
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Returns this user's real name.
	 *
	 * @return string the real name. might be empty
	 */
	public function getRealName() {
		return $this->realName;
	}

	/**
	 * Sets this user's real name.
	 *
	 * @param string $name the user's real name, may be empty.
	 */
	public function setRealName($name) {
		$this->realName = $name;
	}

	/**
	 * Checks whether this user is currently activated.
	 *
	 * This function takes the "disabled" flag, the start date/time and the end date/time into account.
	 *
	 * @return boolean whether this user is currently activated
	 */
	public function isActivated() {
		return !$this->getIsDisabled() && $this->isActivatedViaStartDateAndTime() && $this->isActivatedViaEndDateAndTime();
	}

	/**
	 * Checks whether this user is activated as far as the start date and time is concerned.
	 *
	 * @return boolean whether this user is activated as far as the start date and time is concerned
	 */
	protected function isActivatedViaStartDateAndTime() {
		if ($this->getStartDateAndTime() === NULL) {
			return TRUE;
		}
		$now = new \DateTime('now');
		return $this->getStartDateAndTime() <= $now;
	}

	/**
	 * Checks whether this user is activated as far as the end date and time is concerned.
	 *
	 * @return boolean whether this user is activated as far as the end date and time is concerned
	 */
	protected function isActivatedViaEndDateAndTime() {
		if ($this->getEndDateAndTime() === NULL) {
			return TRUE;
		}
		$now = new \DateTime('now');
		return $now <= $this->getEndDateAndTime();
	}

	/**
	 * Sets whether the IP lock for this user is disabled.
	 *
	 * @param boolean $disableIpLock whether the IP lock for this user is disabled
	 * @return void
	 */
	public function setIpLockIsDisabled($disableIpLock) {
		$this->ipLockIsDisabled = $disableIpLock;
	}

	/**
	 * Checks whether the IP lock for this user is disabled.
	 *
	 * @return boolean whether the IP lock for this user is disabled
	 */
	public function getIpLockIsDisabled() {
		return $this->ipLockIsDisabled;
	}

	/**
	 * Gets this user's last login date and time.
	 *
	 * @return \DateTime|NULL this user's last login date and time, will be NULL if this user has never logged in before
	 */
	public function getLastLoginDateAndTime() {
		return $this->lastLoginDateAndTime;
	}

	/**
	 * Sets this user's last login date and time.
	 *
	 * @param \DateTime|NULL $dateAndTime this user's last login date and time
	 * @return void
	 */
	public function setLastLoginDateAndTime(\DateTime $dateAndTime = NULL) {
		$this->lastLoginDateAndTime = $dateAndTime;
	}
}

?>