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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Main model for extension configuration categories
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ConfigurationCategory extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory>
	 */
	protected $subcategories;

	/**
	 * @var string
	 */
	protected $highlightText = '';

	/**
	 * Constructs this Category
	 */
	public function __construct() {
		$this->subcategories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $subcategories
	 * @return void
	 */
	public function setSubcategories($subcategories) {
		$this->subcategories = $subcategories;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function getSubcategories() {
		return $this->subcategories;
	}

	/**
	 * Adds a subcategories
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory $subcategory
	 * @return void
	 */
	public function addSubcategory(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory $subcategory) {
		$this->subcategories->attach($subcategory);
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
	 * @param string $highlightText
	 * @return void
	 */
	public function setHighlightText($highlightText) {
		$this->highlightText = $highlightText;
	}

	/**
	 * @return string
	 */
	public function getHighlightText() {
		return $this->highlightText;
	}

}


?>