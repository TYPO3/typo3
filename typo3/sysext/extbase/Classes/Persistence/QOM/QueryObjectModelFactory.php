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
 * The Query Object Model Factory
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: QueryObjectModelFactory.php 1972 2010-03-08 16:59:20Z jocrau $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_QueryObjectModelFactory implements Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface {
// SK: Needs to be cleaned up (methods might need to be removed, and comments fixed)
	/**
	 * @var Tx_Extbase_Persistence_Storage_BackendInterface
	 */
	protected $storageBackend;

	/**
	 * Constructs the Component Factory
	 *
	 * @param Tx_Extbase_Persistence_Storage_BackendInterfasce $storageBackend
	 * @param Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper
	 */
	public function __construct(Tx_Extbase_Persistence_Storage_BackendInterface $storageBackend) {
		$this->storageBackend = $storageBackend;
	}

	/**
	 * Creates a query with one or more selectors.
	 * If source is a selector, that selector is the default selector of the
	 * query. Otherwise the query does not have a default selector.
	 *
	 * If the query is invalid, this method throws an InvalidQueryException.
	 * See the individual QOM factory methods for the validity criteria of each
	 * query element.
	 *
	 * @param mixed $source the Selector or the node-tuple Source; non-null
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint the constraint, or null if none
	 * @param array $orderings zero or more orderings; null is equivalent to a zero-length array
	 * @param array $columns the columns; null is equivalent to a zero-length array
	 * @return Tx_Extbase_Persistence_QOM_QueryObjectModelInterface the query; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if a particular validity test is possible on this method, the implemention chooses to perform that test and the parameters given fail that test. See the individual QOM factory methods for the validity criteria of each query element.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function createQuery(Tx_Extbase_Persistence_QOM_SourceInterface $selectorOrSource, $constraint, array $orderings, array $columns) {
		$query =  new Tx_Extbase_Persistence_QOM_QueryObjectModel($selectorOrSource, $constraint, $orderings, $columns);
		$query->injectStorageBackend($this->storageBackend);
		return $query;
	}

	/**
	 * Selects a subset of the nodes in the repository based on node type.
	 *
	 * @param string $nodeTypeName the name of the required node type; non-null
	 * @param string $selectorName the selector name; optional
	 * @return Tx_Extbase_Persistence_QOM_SelectorInterface the selector
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function selector($nodeTypeName, $selectorName = '') {
		if ($selectorName === '') {
			$selectorName = $nodeTypeName;
		}
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Selector', $selectorName, $nodeTypeName);
	}
	
	/**
	 * Sets a statement as constraint. This is not part of the JCR 2.0 Specification!
	 *
	 * @param string $statement The statement
	 * @param array $boundVariables An array of variables to bind to the statement
	 * @param object $language The language of the statement. Must be a supported languanguage defined as Tx_Extbase_Persistence_QOM_QueryObjectModelFactory::TYPO3_*
	 * @return Tx_Extbase_Persistence_QOM_StatementInterface
	 */
	public function statement($statement, array $boundVariables = array(), $language = Tx_Extbase_Persistence_QOM_Statement::TYPO3_SQL_MYSQL) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Statement', $statement, $boundVariables, $language);
	}

	/**
	 * Performs a join between two node-tuple sources.
	 *
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $left the left node-tuple source; non-null
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $right the right node-tuple source; non-null
	 * @param string $joinType one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
	 * @param Tx_Extbase_Persistence_QOM_JoinConditionInterface $join Condition the join condition; non-null
	 * @return Tx_Extbase_Persistence_QOM_JoinInterface the join; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function join(Tx_Extbase_Persistence_QOM_SourceInterface $left, Tx_Extbase_Persistence_QOM_SourceInterface $right, $joinType, Tx_Extbase_Persistence_QOM_JoinConditionInterface $joinCondition) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Join', $left, $right, $joinType, $joinCondition);
	}

	/**
	 * Tests whether the value of a property in a first selector is equal to the value of a property in a second selector.
	 *
	 * @param string $selector1Name the name of the first selector; non-null
	 * @param string $property1Name the property name in the first selector; non-null
	 * @param string $selector2Name the name of the second selector; non-null
	 * @param string $property2Name the property name in the second selector; non-null
	 * @return Tx_Extbase_Persistence_QOM_EquiJoinConditionInterface the constraint; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_EquiJoinCondition', $selector1Name, $property1Name, $selector2Name, $property2Name);
	}

	/**
	 * Performs a logical conjunction of two other constraints.
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint1 the first constraint; non-null
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint2 the second constraint; non-null
	 * @return Tx_Extbase_Persistence_QOM_AndInterface the And constraint; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function _and(Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint1, Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint2) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_LogicalAnd', $constraint1, $constraint2);
	}

	/**
	 * Performs a logical disjunction of two other constraints.
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint1 the first constraint; non-null
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint2 the second constraint; non-null
	 * @return Tx_Extbase_Persistence_QOM_OrInterface the Or constraint; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function _or(Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint1, Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint2) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_LogicalOr', $constraint1, $constraint2);
	}

	/**
	 * Performs a logical negation of another constraint.
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint the constraint to be negated; non-null
	 * @return Tx_Extbase_Persistence_QOM_NotInterface the Not constraint; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function not(Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_LogicalNot', $constraint);
	}
	
	/**
	 * Filters node-tuples based on the outcome of a binary operation.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand1 the first operand; non-null
	 * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
	 * @param Tx_Extbase_Persistence_QOM_StaticOperandInterface $operand2 the second operand; non-null
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface the constraint; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function comparison(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand1, $operator, $operand2) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Comparison', $operand1, $operator, $operand2);
	}
	
	/**
	 * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_PropertyValueInterface the operand; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function propertyValue($propertyName, $selectorName = '') {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_PropertyValue', $propertyName, $selectorName);
	}
	
	/**
	 * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand the operand whose value is converted to a lower-case string; non-null
	 * @return Tx_Extbase_Persistence_QOM_LowerCaseInterface the operand; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function lowerCase(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_LowerCase', $operand);
	}

	/**
	 * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand the operand whose value is converted to a upper-case string; non-null
	 * @return Tx_Extbase_Persistence_QOM_UpperCaseInterface the operand; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function upperCase(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_UpperCase', $operand);
	}
	
	/**
	 * Orders by the value of the specified operand, in ascending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand the operand by which to order; non-null
	 * @return Tx_Extbase_Persistence_QOM_OrderingInterface the ordering
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function ascending(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Ordering', $operand, Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING);
	}

	/**
	 * Orders by the value of the specified operand, in descending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand the operand by which to order; non-null
	 * @return Tx_Extbase_Persistence_QOM_OrderingInterface the ordering
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function descending(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Ordering', $operand, Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING);
	}
	
	/**
	 * Evaluates to the value of a bind variable.
	 *
	 * @param string $bindVariableName the bind variable name; non-null
	 * @return Tx_Extbase_Persistence_QOM_BindVariableValueInterface the operand; non-null
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function bindVariable($bindVariableName) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_BindVariableValue', $bindVariableName);
	}
	
}
?>
