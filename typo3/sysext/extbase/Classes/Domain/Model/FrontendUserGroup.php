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
 * @subpackage Domain
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
	 * @var string
	 */
	// FIXME Support for subgroups
	//protected $subgroup;

	/**
	 * Constructs a new Frontend User Group
	 *
	 */
	public function __construct($title) {
	}
	
	/**
	 * Sets the title value
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * Returns the title value
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the lockToDomain value
	 *
	 * @param string $lockToDomain
	 * @return void
	 */
	public function setLockToDomain($lockToDomain) {
		$this->lockToDomain = $lockToDomain;
	}
	
	/**
	 * Returns the lockToDomain value
	 *
	 * @return string
	 */
	public function getLockToDomain() {
		return $this->lockToDomain;
	}
	
	/**
	 * Sets the description value
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}
	
	/**
	 * Returns the description value
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}
	
	/**
	 * Sets the subgroup value
	 *
	 * @param Tx_Extbase_Domain_Model_FrontendUserGroup $subgroup
	 * @return void
	 */
	public function setSubgroup($subgroup) {
		$this->subgroup = $subgroup;
	}
	
	/**
	 * Returns the subgroup value
	 *
	 * @return Tx_Extbase_Domain_Model_FrontendUserGroup
	 */
	public function getSubgroup() {
		return $this->subgroup;
	}
	
}
?>