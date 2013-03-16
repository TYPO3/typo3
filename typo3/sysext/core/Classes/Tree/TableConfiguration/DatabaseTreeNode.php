<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Ritter <info@steffen-ritter.net>
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
 * Represents a node in a TCA database setup
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
class DatabaseTreeNode extends \TYPO3\CMS\Backend\Tree\TreeRepresentationNode {

	/**
	 * @var boolean
	 */
	protected $selectable;

	/**
	 * @var boolean
	 */
	protected $selected = FALSE;

	/**
	 * @var boolean
	 */
	protected $expanded = TRUE;

	/**
	 * @var boolean
	 */
	protected $hasChildren = FALSE;

	/**
	 * @var mixed
	 */
	private $sortValue;

	/**
	 * Sets the expand state
	 *
	 * @param $expanded
	 * @return void
	 */
	public function setExpanded($expanded) {
		$this->expanded = $expanded;
	}

	/**
	 * Gets the expand state
	 *
	 * @return boolean
	 */
	public function getExpanded() {
		return $this->expanded;
	}

	/**
	 * Sets the selectable property
	 *
	 * @param boolean $selectable
	 * @return void
	 */
	public function setSelectable($selectable) {
		$this->selectable = $selectable;
	}

	/**
	 * Gets the selectable property
	 *
	 * @return boolean
	 */
	public function getSelectable() {
		return $this->selectable;
	}

	/**
	 * Sets the select state
	 *
	 * @param boolean $selected
	 * @return void
	 */
	public function setSelected($selected) {
		$this->selected = $selected;
	}

	/**
	 * Gets the select state
	 *
	 * @return boolean
	 */
	public function getSelected() {
		return $this->selected;
	}

	/**
	 * Gets the hasChildren property
	 *
	 * @return boolean
	 */
	public function hasChildren() {
		return $this->hasChildren;
	}

	/**
	 * Sets the hasChildren property
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setHasChildren($value) {
		$this->hasChildren = (bool) $value;
	}

	/**
	 * Compares a node to another one.
	 *
	 * Returns:
	 * 1 if its greater than the other one
	 * -1 if its smaller than the other one
	 * 0 if its equal
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $other
	 * @return integer see description above
	 */
	public function compareTo($other) {
		if ($this->equals($other)) {
			return 0;
		}
		return $this->sortValue > $other->getSortValue() ? 1 : -1;
	}

	/**
	 * Gets the sort value
	 *
	 * @return mixed
	 */
	public function getSortValue() {
		return $this->sortValue;
	}

	/**
	 * Sets the sort value
	 *
	 * @param mixed $sortValue
	 * @return void
	 */
	public function setSortValue($sortValue) {
		$this->sortValue = $sortValue;
	}

}


?>