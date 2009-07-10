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
 * A persistence query interface
 *
 * @package TYPO3
 * @subpackage Extbase
 * @version $Id: QueryInterface.php 658 2009-05-16 13:54:16Z jocrau $
 */
interface Tx_Extbase_Persistence_QueryInterface {

	/**
	 * Constants representing the direction when ordering result sets.
	 */
	const ORDER_ASCENDING = 'ASC';
	const ORDER_DESCENDING = 'DESC';

	/**
	 * Executes the query against the backend and returns the result
	 *
	 * @return array The query result, an array of objects
	 */
	public function execute();

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 *  'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $orderings The property names to order by
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setOrderings(array $orderings);

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setLimit($limit);

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setOffset($offset);

	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param object $constraint Some constraint, depending on the backend
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function matching($constraint);

	/**
	 * Performs a logical conjunction of the two given constraints.
	 *
	 * @param object $constraint1 First constraint
	 * @param object $constraint2 Second constraint
	 * @return object
	 */
	public function logicalAnd($constraint1, $constraint2);

	/**
	 * Performs a logical disjunction of the two given constraints
	 *
	 * @param object $constraint1 First constraint
	 * @param object $constraint2 Second constraint
	 * @return object
	 */
	public function logicalOr($constraint1, $constraint2);

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return object
	 */
	public function logicalNot($constraint);

	/**
	 * Matches against the (internal) identifier.
	 *
	 * @param string $uid An identifier to match against
	 * @return object
	 */
	public function withUid($uid);

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive
	 * @return object
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE);

	/**
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 */
	public function like($propertyName, $operand);

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 */
	public function lessThan($propertyName, $operand);

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 */
	public function lessThanOrEqual($propertyName, $operand);

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 */
	public function greaterThan($propertyName, $operand);

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 */
	public function greaterThanOrEqual($propertyName, $operand);

}
?>