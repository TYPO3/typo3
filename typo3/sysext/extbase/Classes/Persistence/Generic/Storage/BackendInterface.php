<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 * Storage backend interface
 */
interface BackendInterface {

	/**
	 * Adds a row to the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to insert
	 * @param boolean $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
	 * @return mixed|void
	 */
	public function addRow($tableName, array $row, $isRelation = FALSE);

	/**
	 * Updates a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to update
	 * @param boolean $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
	 * @return mixed|void
	 */
	public function updateRow($tableName, array $row, $isRelation = FALSE);

	/**
	 * Deletes a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $identifier An array of identifier array('fieldname' => value). This array will be transformed to a WHERE clause
	 * @param boolean $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
	 * @return mixed|void
	 */
	public function removeRow($tableName, array $identifier, $isRelation = FALSE);

	/**
	 * Returns the number of items matching the query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query);

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query);
}

?>