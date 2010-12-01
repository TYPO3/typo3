<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer Database Fieldname Iterator
 *
 * @todo Andy Grunwald, 01.12.2010, matching the class name convention? new name tx_fal_iterator_DatabaseFieldnameIterator ?
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_DatabaseFieldnameIterator implements Iterator {

	/**
	 * All pathes that should be iterated over
	 *
	 * @var	array
	 */
	protected $tablesAndFieldsToIterate = array();

	/**
	 * Tablenames
	 *
	 * @var	array
	 */
	protected $tableNames = array();

	/**
	 * Points to the current table
	 *
	 * @var	integer
	 */
	protected $tablenamePointer = 0;

	/**
	 * Points to the current fieldname
	 *
	 * @var	integer
	 */
	protected $fieldnamePointer = 0;

	/**
	 * Constructor of the file iterator
	 *
	 * @return	void
	 */
	public function __construct() {
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tablesAndFieldsToMigrate'] as $extKey => $tableAndFields) {
			foreach ($tableAndFields as $table => $fields) {
				if (!isset($this->tablesAndFieldsToIterate[$table])) {
					$this->tablesAndFieldsToIterate[$table] = array();
				}

				$this->tablesAndFieldsToIterate[$table] = array_merge(
					$this->tablesAndFieldsToIterate[$table],
					(array) $fields
				);
			}
		}

		$this->tableNames = array_keys($this->tablesAndFieldsToIterate);
	}

	/**
	 * Getter for tableNames
	 *
	 * @return	array	DESCRIPTION
	 */
	public function getTableNames() {
		return $this->tableNames;
	}

	/**
	 * Only keep tableNames that are given and reindex the array
	 *
	 * @param	array	$keepTableNames		DESCRIPTION
	 * @return	void
	 */
	public function limitTablesTo(array $keepTableNames) {
		$this->tableNames = array_values(array_intersect($this->tableNames, $keepTableNames));
	}

	/**
	 * Get the current fieldname
	 *
	 * @return	string		DESCRIPTION
	 */
	public function current() {
		return $this->tablesAndFieldsToIterate[$this->tableNames[$this->tablenamePointer]][$this->fieldnamePointer];
	}

	/**
	 * Get the current tablename
	 *
	 * @return	string		DESCRIPTION
	 */
	public function key() {
		return $this->tableNames[$this->tablenamePointer];
	}

	/**
	 * Get next result
	 *
	 * @return	void
	 */
	public function next() {
		$tableName = $this->tableNames[$this->tablenamePointer];

		if ($this->fieldnamePointer < count($this->tablesAndFieldsToIterate[$tableName]) - 1) {
			$this->fieldnamePointer++;
		} else {
			$this->tablenamePointer++;
			$this->fieldnamePointer = 0;
		}
	}

	/**
	 * Reset all pointers to begin new
	 *
	 * @return	void
	 */
	public function rewind() {
		$this->tablenamePointer = 0;
		$this->fieldnamePointer = 0;
	}

	/**
	 * Check if more iterations are possible
	 *
	 * @return	boolean		DESCRIPTION
	 */
	public function valid() {
		$result = FALSE;

		if ($this->tablenamePointer < count($this->tableNames) - 1) {
			$result = TRUE;
		} elseif (isset($this->tableNames[$this->tablenamePointer]) AND
				$this->fieldnamePointer < count($this->tablesAndFieldsToIterate[$this->tableNames[$this->tablenamePointer]])) {
			$result = TRUE;
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/iterator/class.tx_fal_databasefieldnameiterator.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/iterator/class.tx_fal_databasefieldnameiterator.php']);
}
?>