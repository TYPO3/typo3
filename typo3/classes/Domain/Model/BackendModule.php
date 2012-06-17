<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 - Susanne Moog <typo3@susannemoog.de>
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
 * Model for menu entries
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package TYPO3
 * @subpackage core
 */
class Typo3_Domain_Model_BackendModule {

	/**
	 * @var string $title
	 */
	protected $title = '';

	/**
	 * @var string $name
	 */
	protected $name = '';

	/**
	 * @var array $icon
	 */
	protected $icon = array();

	/**
	 * @var string $link
	 */
	protected $link = '';

	/**
	 * @var string $onClick
	 */
	protected $onClick = '';

	/**
	 * @var string $description
	 */
	protected $description = '';

	/**
	 * @var string $navigationComponentId
	 */
	protected $navigationComponentId = '';

	/**
	 * @var SplObjectStorage $children
	 */
	protected $children;

	/**
	 * construct
	 */
	public function __construct() {
		$this->children = new SplObjectStorage();
	}

	/**
	 * @param \SplObjectStorage $children
	 */
	public function setChildren($children) {
		$this->children = $children;
	}

	/**
	 * @return \SplObjectStorage
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * @param Typo3_BackendModule $child
	 */
	public function addChild(Typo3_Domain_Model_BackendModule $child) {
		$this->children->attach($child);
	}

	/**
	 * @param array $icon
	 */
	public function setIcon(array $icon) {
		$this->icon = $icon;
	}

	/**
	 * @return array
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @param string $name
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
	 * @param string $link
	 */
	public function setLink($link) {
		$this->link = $link;
	}

	/**
	 * @return string
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $navigationComponentId
	 */
	public function setNavigationComponentId($navigationComponentId) {
		$this->navigationComponentId = $navigationComponentId;
	}

	/**
	 * @return string
	 */
	public function getNavigationComponentId() {
		return $this->navigationComponentId;
	}

	/**
	 * @param string $onClick
	 */
	public function setOnClick($onClick) {
		$this->onClick = $onClick;
	}

	/**
	 * @return string
	 */
	public function getOnClick() {
		return $this->onClick;
	}
}

?>