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
 * Storage backend interface
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: BackendInterface.php 2120 2009-04-02 10:06:31Z k-fish $
 */
interface Tx_Extbase_Persistence_Storage_BackendInterface {

	/**
	 * Adds a row to the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to insert
	 * @return void
	 */
	public function addRow($tableName, array $row);

	/**
	 * Updates a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to update
	 * @return void
	 */
	public function updateRow($tableName, array $row);

	/**
	 * Deletes a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param int $uid The uid of the row to delete
	 * @return void
	 */
	public function removeRow($tableName, $uid);

	/**
	 * Returns an array with rows matching the query.
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModelInterface $query
	 * @return array
	 */
	public function getRows(Tx_Extbase_Persistence_QOM_QueryObjectModelInterface $query);

}
?>