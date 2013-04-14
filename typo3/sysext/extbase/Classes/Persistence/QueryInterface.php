<?php
namespace TYPO3\CMS\Extbase\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *  All rights reserved.
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
 * A persistence query interface
 *
 * @api
 */
interface QueryInterface {

	/**
	 * The '=' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_EQUAL_TO = 1;

	/**
	 * The '!=' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_NOT_EQUAL_TO = 2;

	/**
	 * The '<' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_LESS_THAN = 3;

	/**
	 * The '<=' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_LESS_THAN_OR_EQUAL_TO = 4;

	/**
	 * The '>' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_GREATER_THAN = 5;

	/**
	 * The '>=' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_GREATER_THAN_OR_EQUAL_TO = 6;

	/**
	 * The 'like' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_LIKE = 7;

	/**
	 * The 'contains' comparison operator for collections.
	 *
	 * @api
	 */
	const OPERATOR_CONTAINS = 8;

	/**
	 * The 'in' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_IN = 9;

	/**
	 * The 'is NULL' comparison operator.
	 *
	 * @api
	 */
	const OPERATOR_IS_NULL = 10;

	/**
	 * The 'is empty' comparison operator for collections.
	 *
	 * @api
	 */
	const OPERATOR_IS_EMPTY = 11;

	/**
	 * Constants representing the direction when ordering result sets.
	 */
	const ORDER_ASCENDING = 'ASC';
	const ORDER_DESCENDING = 'DESC';

	/**
	 * Gets the node-tuple source for this query.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface the node-tuple source; non-NULL
	 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
	 */
	public function getSource();

	/**
	 * Executes the query and returns the result.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array The query result object or an array if $this->getQuerySettings()->getReturnRawQueryResult() is TRUE
	 * @api
	 */
	public function execute();

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $orderings The property names to order by
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function setOrderings(array $orderings);

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface).
	 *
	 * @param integer $limit
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function setLimit($limit);

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface).
	 *
	 * @param integer $offset
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function setOffset($offset);

	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface).
	 *
	 * @param object $constraint Some constraint, depending on the backend
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function matching($constraint);

	/**
	 * Performs a logical conjunction of the two given constraints. The method
	 * takes one or more constraints and concatenates them with a boolean AND.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalAnd($constraint1);

	/**
	 * Performs a logical disjunction of the two given constraints. The method
	 * takes one or more constraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalOr($constraint1);

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return object
	 * @api
	 */
	public function logicalNot($constraint);

	/**
	 * Returns an equals criterion used for matching objects against a query.
	 *
	 * It matches if the $operand equals the value of the property named
	 * $propertyName. If $operand is NULL a strict check for NULL is done. For
	 * strings the comparison can be done with or without case-sensitivity.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive for strings
	 * @return object
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE);

	/**
	 * Returns a like criterion used for matching objects against a query.
	 * Matches if the property named $propertyName is like the $operand, using
	 * standard SQL wildcards.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param string $operand The value to compare with
	 * @param boolean $caseSensitive Whether the matching should be done case-sensitive
	 * @return object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a non-string property
	 * @api
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE);

	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * If NULL is given as $operand, there will never be a match!
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function contains($propertyName, $operand);

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a multi-valued property
	 * @api
	 */
	public function in($propertyName, $operand);

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThan($propertyName, $operand);

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand);

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThan($propertyName, $operand);

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand);

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
	 * @api
	 */
	public function getType();

	/**
	 * Sets the Query Settings. These Query settings must match the settings expected by
	 * the specific Storage Backend.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The Query Settings
	 * @return void
	 * @todo decide whether this can be deprecated somewhen
	 * @api This method is not part of TYPO3Flow API
	 */
	public function setQuerySettings(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings);

	/**
	 * Returns the Query Settings.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The Query Settings
	 * @todo decide whether this can be deprecated somewhen
	 * @api This method is not part of  TYPO3Flow API
	 */
	public function getQuerySettings();

	/**
	 * Returns the query result count.
	 *
	 * @return integer The query result count
	 * @api
	 */
	public function count();

	/**
	 * Gets the property names to order the result by, like this:
	 * array(
	 *  'foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 * @api
	 */
	public function getOrderings();

	/**
	 * Returns the maximum size of the result set to limit.
	 *
	 * @return integer
	 * @api
	 */
	public function getLimit();

	/**
	 * Returns the start offset of the result set.
	 *
	 * @return integer
	 * @api
	 */
	public function getOffset();

	/**
	 * Gets the constraint for this query.
	 *
	 * @return mixed the constraint, or null if none
	 * @api
	 */
	public function getConstraint();

	/**
	 * Returns an "isEmpty" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains no values or is NULL.
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @return boolean
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function isEmpty($propertyName);
}

?>