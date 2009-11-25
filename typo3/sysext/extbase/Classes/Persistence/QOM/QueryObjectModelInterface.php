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
 * A query in the JCR query object model.
 *
 * The JCR query object model describes the queries that can be evaluated by a JCR
 * repository independent of any particular query language, such as SQL.
 *
 * A query consists of:
 *
 * a source. When the query is evaluated, the source evaluates its selectors and
 * the joins between them to produce a (possibly empty) set of node-tuples. This
 * is a set of 1-tuples if the query has one selector (and therefore no joins), a
 * set of 2-tuples if the query has two selectors (and therefore one join), a set
 * of 3-tuples if the query has three selectors (two joins), and so forth.
 * an optional constraint. When the query is evaluated, the constraint filters the
 * set of node-tuples.
 * a list of zero or more orderings. The orderings specify the order in which the
 * node-tuples appear in the query results. The relative order of two node-tuples
 * is determined by evaluating the specified orderings, in list order, until
 * encountering an ordering for which one node-tuple precedes the other. If no
 * orderings are specified, or if for none of the specified orderings does one
 * node-tuple precede the other, then the relative order of the node-tuples is
 * implementation determined (and may be arbitrary).
 * a list of zero or more columns to include in the tabular view of the query
 * results. If no columns are specified, the columns available in the tabular view
 * are implementation determined, but minimally include, for each selector, a column
 * for each single-valued non-residual property of the selector's node type.
 *
 * The query object model representation of a query is created by factory methods in the QueryObjectModelFactory.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: QueryObjectModelInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_QOM_QueryObjectModelInterface {

	/**
	 * Flags determining the language of the query
	 */
	const JCR_JQOM = 'JCR-JQOM';
	const TYPO3_SQL_MYSQL = 'TYPO3-SQL-MYSQL';
	
	/**
	 * Gets the node-tuple source for this query.
	 *
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface the node-tuple source; non-null
	*/
	public function getSource();

	/**
	 * Gets the constraint for this query.
	 *
	 * @return Tx_Extbase_Persistence_QOM_ConstraintInterface the constraint, or null if none
	*/
	public function getConstraint();

	/**
	 * Gets the orderings for this query.
	 *
	 * @return array an array of zero or more Tx_Extbase_Persistence_QOM_OrderingInterface; non-null
	*/
	public function getOrderings();

	/**
	 * Gets the columns for this query.
	 *
	 * @return array an array of zero or more Tx_Extbase_Persistence_QOM_ColumnInterface; non-null
	*/
	public function getColumns();

	/**
	 * Backend specific query settings
	 * 
	 * @return Tx_Extbase_Persistence_Storage_QuerySettingsInterface Backend specific query settings
	 */
	public function getQuerySettings();

	/**
	 * Binds the given value to the variable named $varName.
	 *
	 * @param string $varName name of variable in query
	 * @param Tx_Extbase_Persistence_ValueInterface $value value to bind
	 * @return void
	 * @throws InvalidArgumentException if $varName is not a valid variable in this query.
	 * @throws RepositoryException if an error occurs.
	 */
	public function bindValue($varName, Tx_Extbase_Persistence_ValueInterface $value);

}

?>