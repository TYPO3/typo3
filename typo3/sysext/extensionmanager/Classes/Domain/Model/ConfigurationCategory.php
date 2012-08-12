<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * @package Extension Manager
 * @subpackage Model
 */
class Tx_Extensionmanager_Domain_Model_ConfigurationCategory extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extensionmanager_Domain_Model_ConfigurationSubcategory>
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
		$this->subcategories = new Tx_Extbase_Persistence_ObjectStorage();

	}

	/**
	 * @param \Tx_Extbase_Persistence_ObjectStorage $subcategories
	 * @return void
	 */
	public function setSubcategories($subcategories) {
		$this->subcategories = $subcategories;
	}

	/**
	 * @return \Tx_Extbase_Persistence_ObjectStorage
	 */
	public function getSubcategories() {
		return $this->subcategories;
	}

	/**
	 * Adds a subcategories
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationSubcategory $subcategory
	 * @return void
	 */
	public function addSubcategory(Tx_Extensionmanager_Domain_Model_ConfigurationSubcategory $subcategory) {
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