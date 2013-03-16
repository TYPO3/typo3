<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <typo3@susannemoog.de>
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
 * Model for configuration sub categories
 *
 * Configuration options can be structured with categories and sub categories.
 * Categories are usually displayed as tabs and sub categories are used to
 * group configuration items in one tab.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ConfigurationSubcategory extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var string The sub category label
	 */
	protected $label = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem>
	 */
	protected $items;

	/**
	 * Constructs this Category
	 */
	public function __construct() {
		$this->items = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $items
	 * @return void
	 */
	public function setItems($items) {
		$this->items = $items;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * Adds a subcategory
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $item
	 * @return void
	 */
	public function addItem(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $item) {
		$this->items->attach($item);
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set sub category label
	 *
	 * @param string $label
	 * @return void
	 */
	public function setLabel($label) {
		$this->label = $label;
	}

	/**
	 * Get sub category label
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

}


?>