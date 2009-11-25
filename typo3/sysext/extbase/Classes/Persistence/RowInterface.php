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
 * @version $Id: RowInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_RowInterface {

	/**
	 * @return boolean TRUE if the columnName is set
	 */
	public function hasValue($columnName);

	/**
	 * Returns an array of all the values in the same order as the column names
	 * returned by QueryResult.getColumnNames().
	 *
	 * @return array a Value array.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if an error occurs
	 */
	public function getValues();

	/**
	 * Returns the value of the indicated column in this Row.
	 *
	 * @param string $columnName name of query result table column
	 * @return \F3\PHPCR\ValueInterface a Value
	 * @throws \F3\PHPCR\ItemNotFoundException if columnName s not among the column names of the query result table.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getValue($columnName);

}
?>