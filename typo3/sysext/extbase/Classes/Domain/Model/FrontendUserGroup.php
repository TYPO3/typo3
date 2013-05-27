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
 * A Frontend User Group
 *
 * @api
 */
class FrontendUserGroup extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 */
	protected $title = '';

	/**
	 * @var string
	 */
	protected $lockToDomain = '';

	/**
	 * @var string
	 */
	protected $description = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup>
	 */
	protected $subgroup;

	/**
	 * Constructs a new Frontend User Group
	 *
	 * @param string $title
	 */
	public function __construct($title = '') {
		$this->setTitle($title);
		$this->subgroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
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
	 * Sets the subgroups. Keep in mind that the property is called "subgroup"
	 * although it can hold several subgroups.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $subgroup An object storage containing the subgroups to add
	 * @return void
	 * @api
	 */
	public function setSubgroup(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $subgroup) {
		$this->subgroup = $subgroup;
	}

	/**
	 * Adds a subgroup to the frontend user
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $subgroup
	 * @return void
	 * @api
	 */
	public function addSubgroup(\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $subgroup) {
		$this->subgroup->attach($subgroup);
	}

	/**
	 * Removes a subgroup from the frontend user group
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $subgroup
	 * @return void
	 * @api
	 */
	public function removeSubgroup(\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $subgroup) {
		$this->subgroup->detach($subgroup);
	}

	/**
	 * Returns the subgroups. Keep in mind that the property is called "subgroup"
	 * although it can hold several subgroups.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage An object storage containing the subgroups
	 * @api
	 */
	public function getSubgroup() {
		return $this->subgroup;
	}
}

?>