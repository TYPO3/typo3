<?php
namespace TYPO3\CMS\Beuser\Domain\Model;

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
 */
class BackendUser extends \TYPO3\CMS\Extbase\Domain\Model\BackendUser {

	/**
	 * Comma separated list of uids in multi-select
	 * Might retrieve the labels from TCA/DataMapper
	 *
	 * @var string
	 */
	protected $allowedLanguages = '';

	/**
	 * @var string
	 */
	protected $dbMountPoints = '';

	/**
	 * @var string
	 */
	protected $fileMountPoints = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup>
	 */
	protected $backendUserGroups;

	/**
	 * @param string $allowedLanguages
	 * @return void
	 */
	public function setAllowedLanguages($allowedLanguages) {
		$this->allowedLanguages = $allowedLanguages;
	}

	/**
	 * @return string
	 */
	public function getAllowedLanguages() {
		return $this->allowedLanguages;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setDbMountPoints($dbMountPoints) {
		$this->dbMountPoints = $dbMountPoints;
	}

	/**
	 * @return string
	 */
	public function getDbMountPoints() {
		return $this->dbMountPoints;
	}

	/**
	 * @param string $fileMountPoints
	 * @return void
	 */
	public function setFileMountPoints($fileMountPoints) {
		$this->fileMountPoints = $fileMountPoints;
	}

	/**
	 * @return string
	 */
	public function getFileMountPoints() {
		return $this->fileMountPoints;
	}

	/**
	 * Check if user is active, not disabled
	 *
	 * @return boolean
	 */
	public function isActive() {
		if ($this->getIsDisabled()) {
			return FALSE;
		}
		$now = new \DateTime('now');
		return !$this->getStartDateAndTime() && !$this->getEndDateAndTime() || $this->getStartDateAndTime() <= $now && (!$this->getEndDateAndTime() || $this->getEndDateAndTime() > $now);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $backendUserGroups
	 */
	public function setBackendUserGroups($backendUserGroups) {
		$this->backendUserGroups = $backendUserGroups;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function getBackendUserGroups() {
		return $this->backendUserGroups;
	}

}

?>