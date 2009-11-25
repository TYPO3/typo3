<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * A row in the query result table.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: Row.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_Row implements Tx_Extbase_Persistence_RowInterface {

	/**
	 * @var array
	 */
	protected $tuple;

	/**
	 * Constructs this Row instance
	 *
	 * @param array $tuple
	 */
	public function __construct(array $tuple = array()) {
		$this->tuple = $tuple;
	}

	/**
	 * @return boolean TRUE if the columnName is set
	 */
	public function hasValue($columnName) {
		return $this->offsetExists($columnName);
	}

	/**
	 * Returns an array of all the values in the same order as the column names
	 * returned by QueryResult.getColumnNames().
	 *
	 * @return array a Value array.
	 */
	public function getValues() {
		return array_values($this->tuple);
	}

	/**
	 * Returns the value of the indicated column in this Row.
	 *
	 * @param string $columnName name of query result table column
	 * @return Tx_Extbase_Persistence_ValueInterface a Value
	 */
	public function getValue($columnName) {
		return $this->offsetGet($columnName);
	}

	/**
	 * Loads the row at a given offset.
	 *
	 * @param string $offset
	 * @param mixed $value The value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->tuple[$offset] = $value;
	}

	/**
	 * Checks if a given offset exists in the row
	 *
	 * @param string $offset
	 * @return boolean TRUE if the given offset exists; otherwise FALSE
	 */
	public function offsetExists($offset) {
		return isset($this->tuple[$offset]);
	}

	/**
	 * Unsets the storage at the given offset
	 *
	 * @param string $offset The offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->tuple[$offset]);
	}

	/**
	 * Returns the value at the given offset
	 *
	 * @param string $offset The offset
	 * @return The value|NULL if the offset does not exist
	 */
	public function offsetGet($offset) {
		return $this->offsetExists($offset) ? $this->tuple[$offset] : NULL;
	}

}
?>