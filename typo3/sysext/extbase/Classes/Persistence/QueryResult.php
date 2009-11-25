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
 * A QueryResult object. Returned by Query->execute().
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: QueryResult.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QueryResult implements Tx_Extbase_Persistence_QueryResultInterface {

	/**
	 * @var array The rows of the query result
	 */
	protected $rows;

	/**
	 * Constructs this QueryResult
	 *
	 * @param array $rows The
	 */
	public function __construct(array $rows) {
		$this->rows = $rows;
	}

	/**
	 * Returns an array of all the column names in the table view of this result set.
	 *
	 * @return array array holding the column names.
	 */
	public function getColumnNames() {
		if (!is_null($this->rows)) {
			return array_keys($this->rows[0]);
		} else {
			return array();
		}
	}

	/**
	 * Returns an iterator over the Rows of the result table. The rows are
	 * returned according to the ordering specified in the query.
	 *
	 * @return Tx_Extbase_Persistence_RowIteratorInterface a RowIterator
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if this call is the second time either getRows() or getNodes() has been called on the same QueryResult object or if another error occurs.
	*/
	public function getRows() {
		if ($this->rows === NULL) throw new Tx_Extbase_Persistence_Exception_RepositoryException('Illegal getRows() call - can be called only once.', 1237991809);

		$rowIterator = t3lib_div::makeInstance('Tx_Extbase_Persistence_RowIterator');
		foreach ($this->rows as $row) {
			$rowIterator->append(t3lib_div::makeInstance('Tx_Extbase_Persistence_Row', $row));
		}
		$this->rows = NULL;

		return $rowIterator;
	}

}
?>