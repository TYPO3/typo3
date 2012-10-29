<?php
namespace TYPO3\CMS\Extbase\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Fabien Udriot <fabien.udriot@typo3.org>
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
 * This model represents a category (for anything).
 *
 * @author Fabien Udriot <fabien.udriot@typo3.org>
 * @scope prototype
 * @entity
 * @api
 */
class Category extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 * @validate notEmpty
	 */
	protected $title = '';

	/**
	 * @var string
	 */
	protected $description = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\Category|NULL
	 * @lazy
	 */
	protected $parent = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\AbstractEntity>
	 * @lazy
	 */
	protected $items = NULL;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->initStorageObjects();
	}

	/**
	 * Initializes all \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage properties.
	 *
	 * @return void
	 * @api
	 */
	protected function initStorageObjects() {
		$this->items = new \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage();
	}

	/**
	 * Gets the title.
	 *
	 * @return string the title, might be empty
	 * @api
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the title.
	 *
	 * @param string $title the title to set, may be empty
	 * @return void
	 * @api
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Gets the description.
	 *
	 * @return string the description, might be empty
	 * @api
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Sets the description.
	 *
	 * @param string $description the description to set, may be empty
	 * @return void
	 * @api
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Gets the parent category.
	 *
	 * @return \TYPO3\CMS\Extbase\Domain\Model\Category|NULL the parent category
	 * @api
	 */
	public function getParent() {
		if ($this->parent instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
			$this->parent->_loadRealInstance();
		}
		return $this->parent;
	}

	/**
	 * Sets the parent category.
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\Category $parent the parent category
	 * @return void
	 * @api
	 */
	public function setParent(\TYPO3\CMS\Extbase\Domain\Model\Category $parent) {
		$this->parent = $parent;
	}

	/**
	 * Returns the items within this category.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage the items, might be empty
	 * @api
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * Sets the items within this category.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage $items
	 * @return void
	 * @api
	 */
	public function setItems(\TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage $items) {
		$this->items = $items;
	}

	/**
	 * Adds a item to the items property
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $item
	 * @return void
	 * @api
	 */
	public function addItem(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity $item) {
		$this->items->attach($item);
	}

	/**
	 * Removes a item of the items property
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $item
	 * @return void
	 * @api
	 */
	public function removeItem(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity $item) {
		$this->items->detach($item);
	}

}


?>