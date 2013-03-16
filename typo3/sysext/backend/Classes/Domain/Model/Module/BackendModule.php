<?php
namespace TYPO3\CMS\Backend\Domain\Model\Module;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <typo3@susannemoog.de>
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
 */
class BackendModule {

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
	 * @var \SplObjectStorage $children
	 */
	protected $children;

	/**
	 * construct
	 */
	public function __construct() {
		$this->children = new \SplObjectStorage();
	}

	/**
	 * Set children
	 *
	 * @param \SplObjectStorage $children
	 * @return void
	 */
	public function setChildren($children) {
		$this->children = $children;
	}

	/**
	 * Get children
	 *
	 * @return \SplObjectStorage
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * Add Child
	 *
	 * @param \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $child
	 * @return void
	 */
	public function addChild(\TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $child) {
		$this->children->attach($child);
	}

	/**
	 * Set icon
	 *
	 * @param array $icon
	 * @return void
	 */
	public function setIcon(array $icon) {
		$this->icon = $icon;
	}

	/**
	 * Get icon
	 *
	 * @return array
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Get Title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set Link
	 *
	 * @param string $link
	 * @return void
	 */
	public function setLink($link) {
		$this->link = $link;
	}

	/**
	 * Get Link
	 *
	 * @return string
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * Set Description
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Set Navigation Component Id
	 *
	 * @param string $navigationComponentId
	 * @return void
	 */
	public function setNavigationComponentId($navigationComponentId) {
		$this->navigationComponentId = $navigationComponentId;
	}

	/**
	 * Get Navigation Component Id
	 *
	 * @return string
	 */
	public function getNavigationComponentId() {
		return $this->navigationComponentId;
	}

	/**
	 * Set onClick
	 *
	 * @param string $onClick
	 * @return void
	 */
	public function setOnClick($onClick) {
		$this->onClick = $onClick;
	}

	/**
	 * Get onClick
	 *
	 * @return string
	 */
	public function getOnClick() {
		return $this->onClick;
	}

}


?>