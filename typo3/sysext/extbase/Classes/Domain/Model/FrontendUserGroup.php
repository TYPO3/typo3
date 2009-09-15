<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * A Frontend User Group
 *
 * @package Extbase
 * @subpackage Domain\Model
 * @version $Id: $
 * @scope prototype
 * @entity
 */
class Tx_Extbase_Domain_Model_FrontendUserGroup extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $lockToDomain;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FrontendUserGroup>
	 */
	protected $subgroups;

	/**
	 * Constructs a new Frontend User Group
	 *
	 */
	public function __construct($title) {
		$this->subgroups = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * Sets the title value
	 *
	 * @param string $title
	 * @return void
	 * @api
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the title value
	 *
	 * @return string
	 * @api
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the lockToDomain value
	 *
	 * @param string $lockToDomain
	 * @return void
	 * @api
	 */
	public function setLockToDomain($lockToDomain) {
		$this->lockToDomain = $lockToDomain;
	}

	/**
	 * Returns the lockToDomain value
	 *
	 * @return string
	 * @api
	 */
	public function getLockToDomain() {
		return $this->lockToDomain;
	}

	/**
	 * Sets the description value
	 *
	 * @param string $description
	 * @return void
	 * @api
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Returns the description value
	 *
	 * @return string
	 * @api
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Sets the subgroups
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FrontendUserGroup> $subgroups An object storage containing the subgroups to add
	 * @return void
	 * @api
	 */
	public function setSubgroups(Tx_Extbase_Persistence_ObjectStorage $subgroups) {
		$this->subgroups = $subgroups;
	}

	/**
	 * Adds a subgroup to the frontend user
	 *
	 * @param Tx_Extbase_Domain_Model_FrontendUserGroup $subgroup
	 * @return void
	 * @api
	 */
	public function addSubgroup(Tx_Extbase_Domain_Model_FrontendUserGroup $subgroup) {
		$this->subgroups->attach($subgroup);
	}

	/**
	 * Removes a subgroup from the frontend user group
	 *
	 * @param Tx_Extbase_Domain_Model_FrontendUserGroup $subgroup
	 * @return void
	 * @api
	 */
	public function removeSubgroup(Tx_Extbase_Domain_Model_FrontendUserGroup $subgroup) {
		$this->subgroups->detach($subgroup);
	}

	/**
	 * Returns the subgroups
	 *
	 * @return Tx_Extbase_Persistence_ObjectStorage An object storage containing the subgroups
	 * @api
	 */
	public function getSubgroups() {
		return $this->subgroups;
	}

}
?>