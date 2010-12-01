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
 * File Abtraction Layer Record Iterator
 *
 * @todo Andy Grunwald, 01.12.2010, matching the class name convention? new name tx_fal_iterator_RecordIterator ?
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_RecordIterator implements Iterator {

	/**
	 * Contains records
	 *
	 * @var	array
	 */
	protected $records = array();

	/**
	 * Points to the current record
	 *
	 * @var	integer
	 */
	protected $recordPointer = 0;

	/**
	 * Tablename of the table the current selection is based on
	 *
	 * @var	string
	 */
	protected $tableName = '';

	/**
	 * Fieldname of the field the current selection is based on
	 *
	 * @var	string
	 */
	protected $fieldName = '';

	/**
	 * Limit for records to be read
	 *
	 * @var	integer
	 */
	protected $limit = 500;

	/**
	 * Constructor of the file iterator
	 *
	 * @return	void
	 */
	public function __construct() {
	}

	/**
	 * Setter for limit
	 *
	 * @param	integer	$limit	DESCRIPTION
	 * @return	void
	 */
	public function setLimit($limit) {
		$this->limit = (int) $limit;
	}

	/**
	 * Fetch records from database for table and fieldname
	 *
	 * @param	string	$tableName	DESCRIPTION
	 * @param	string	$fieldName	DESCRIPTION
	 * @return	void
	 */
	public function fetchRecordsForTableAndField($tableName, $fieldName) {
		$this->tableName = $tableName;
		$this->fieldName = $fieldName;
		$this->records = array();
		$this->recordPointer = 0;

		if (isset($TCA['pages']['ctrl']['delete'])) {
			$enableFields = ' AND ' . $TCA['pages']['ctrl']['delete'] . '=0';
		}

			// only fetch records that has files in the field attached and has no reference count set
			// if reference count is set in FAL_fieldname then the migration for this records was successful earlier
		$this->records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$tableName,
			$fieldName . ' <> \'\' AND '. $fieldName . '_rel = 0' . $enableFields,
			'',
			'',
			$this->limit
		);
	}

	/**
	 * Get the current fieldname
	 *
	 * @return	string	DESCRIPTION
	 */
	public function current() {
		return $this->records[$this->recordPointer][$this->fieldName];
	}

	/**
	 * Get the current tablename
	 *
	 * @return	string	DESCRIPTION
	 */
	public function key() {
		$key = 0;

		if (isset($this->records[$this->recordPointer]['uid'])) {
			$key = $this->records[$this->recordPointer]['uid'];
		} else {
			$key = $this->recordPointer;
		}

		return $key;
	}

	/**
	 * Get next result
	 *
	 * @return	void
	 */
	public function next() {
		$this->recordPointer++;
	}

	/**
	 * Reset all pointers to begin new
	 *
	 * @return	void
	 */
	public function rewind() {
		$this->recordPointer = 0;
	}

	/**
	 * Check if more iterations are possible
	 *
	 * @return	boolean
	 */
	public function valid() {
		$result = FALSE;

		if ($this->recordPointer < count($this->records)) {
			$result = TRUE;
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/iterator/class.tx_fal_recorditerator.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/iterator/class.tx_fal_recorditerator.php']);
}
?>