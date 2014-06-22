<?php
namespace TYPO3\CMS\Beuser\Domain\Model;

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
 * Model for backend user group
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class BackendUserGroup extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup>
	 * @lazy
	 */
	protected $subGroups;

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $subGroups
	 */
	public function setSubGroups($subGroups) {
		$this->subGroups = $subGroups;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function getSubGroups() {
		return $this->subGroups;
	}

}
