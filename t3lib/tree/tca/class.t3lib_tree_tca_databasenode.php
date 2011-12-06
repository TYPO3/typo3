<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Ritter <info@steffen-ritter.net>
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
 * @package TYPO3
 * @subpackage t3lib_tree
 */

class t3lib_tree_Tca_DatabaseNode extends t3lib_tree_ExtJs_Node implements t3lib_tree_RecordBasedNode, t3lib_tree_ComparableNode {


	/**
	 * @var mixed
	 */
	private $sortValue;

	/**
	 * Returns the source field of the label
	 *
	 * @return string
	 */
	public function getTextSourceField() {
		// TODO: Implement getTextSourceField() method.
	}

	/**
	 * set the source field of the label
	 *
	 * @param string $field
	 * @return void
	 */
	public function setTextSourceField($field) {
		// TODO: Implement setTextSourceField() method.
	}

	/**
	 * Sets the database record array
	 *
	 * @param array $record
	 * @return void
	 */
	public function setRecord($record) {
		// TODO: Implement setRecord() method.
	}

	/**
	 * Returns the database record array
	 *
	 * @return array
	 */
	public function getRecord() {
		// TODO: Implement getRecord() method.
	}

	/**
	 * Returns the table of the record data
	 *
	 * @return string
	 */
	public function getSourceTable() {
		// TODO: Implement getSourceTable() method.
	}

	/**
	 * sets the Table of record source data
	 *
	 * @param string $table
	 * @return void
	 */
	public function setSourceTable($table) {
		// TODO: Implement setSourceTable() method.
	}

	/**
	 * Compares a node to another one.
	 *
	 * Returns:
	 * 1 if its greater than the other one
	 * -1 if its smaller than the other one
	 * 0 if its equal
	 *
	 * @param t3lib_tree_Node $other
	 * @return int see description above
	 */
	public function compareTo($other) {
		if ($this->equals($other)) {
			return 0;
		}

		return ($this->sortValue > $other->getSortValue()) ? 1 : -1;
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