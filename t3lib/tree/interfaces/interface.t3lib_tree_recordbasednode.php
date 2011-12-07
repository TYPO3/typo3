<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Interface that defines the nodes based on records
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
interface t3lib_tree_RecordBasedNode {

	/**
	 * Returns the source field of the label
	 *
	 * @return string
	 */
	public function getTextSourceField();

	/**
	 * set the source field of the label
	 *
	 * @param string $field
	 * @return void
	 */
	public function setTextSourceField($field);

	/**
	 * Sets the database record array
	 *
	 * @param array $record
	 * @return void
	 */
	public function setRecord($record);

	/**
	 * Returns the database record array
	 *
	 * @return array
	 */
	public function getRecord();

	/**
	 * Returns the table of the record data
	 *
	 * @return string
	 */
	public function getSourceTable();

	/**
	 * sets the Table of record source data
	 *
	 * @param string $table
	 * @return void
	 */
	public function setSourceTable($table);

}
?>
