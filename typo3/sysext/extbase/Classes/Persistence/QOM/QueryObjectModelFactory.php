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
 * @version $Id: QueryObjectModelFactory.php 1729 2009-11-25 21:37:20Z stucki $
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
	public function __construct(Tx_Extbase_Persistence_Storage_BackendInterface $storageBackend, Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper) {
		$this->storageBackend = $storageBackend;
		$this->dataMapper = $dataMapper;
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
		$query->injectDataMapper($this->dataMapper);
		return $query;
	}

	/**
	 * Selects a subset of the nodes in the repository based on node type.
	 *
	 * @param string $nodeTypeName the name of the required node type; non-null
	 * @param string $selectorName the selector name; optional
	 * @return Tx_Extbase_Persistence_QOM_SelectorInterface the selector
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
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
	 * @param object $language The language of the statement. Must be a supported languanguage defined as Tx_Extbase_Persistence_QOM_QueryObjectModelInterface::JCR_* or Tx_Extbase_Persistence_QOM_QueryObjectModelInterface::TYPO3_* or 
	 * @return Tx_Extbase_Persistence_QOM_StatementInterface
	 */
	public function statement($statement, $boundVariables, $language) {
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
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
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
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_EquiJoinCondition', $selector1Name, $property1Name, $selector2Name, $property2Name);
	}

	/**
	 * Tests whether a first selector's node is the same as a node identified by relative path from a second selector's node.
	 *
	 * @param string $selector1Name the name of the first selector; non-null
	 * @param string $selector2Name the name of the second selector; non-null
	 * @param string $selector2Path the path relative to the second selector; non-null
	 * @return Tx_Extbase_Persistence_QOM_SameNodeJoinConditionInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function sameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058190);
	}

	/**
	 * Tests whether a first selector's node is a child of a second selector's node.
	 *
	 * @param string $childSelectorName the name of the child selector; non-null
	 * @param string $parentSelectorName the name of the parent selector; non-null
	 * @return Tx_Extbase_Persistence_QOM_ChildNodeJoinConditionInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function childNodeJoinCondition($childSelectorName, $parentSelectorName) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058190);
	}

	/**
	 * Tests whether a first selector's node is a descendant of a second selector's node.
	 *
	 * @param string $descendantSelectorName the name of the descendant selector; non-null
	 * @param string $ancestorSelectorName the name of the ancestor selector; non-null
	 * @return Tx_Extbase_Persistence_QOM_DescendantNodeJoinConditionInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function descendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058192);
	}

	/**
	 * Performs a logical conjunction of two other constraints.
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint1 the first constraint; non-null
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint2 the second constraint; non-null
	 * @return Tx_Extbase_Persistence_QOM_AndInterface the And constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
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
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
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
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function not(Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_LogicalNot', $constraint);
	}

	/**
	 * Filters related node-tuples based on an object property.
	 *
	 * @param Tx_Extbase_DomainObject_AbstractEntity $object The object
	 * @param string $propertyName The name of the property of the related object
	 * @return Tx_Extbase_Persistence_QOM_RelatedInterface the constraint; non-null
	 */
	public function related(Tx_Extbase_DomainObject_AbstractEntity $object, $propertyName) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Related', $object, $propertyName);
	}

	/**
	 * Filters node-tuples based on the outcome of a binary operation.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand1 the first operand; non-null
	 * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
	 * @param Tx_Extbase_Persistence_QOM_StaticOperandInterface $operand2 the second operand; non-null
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function comparison(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand1, $operator, Tx_Extbase_Persistence_QOM_StaticOperandInterface $operand2) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Comparison', $operand1, $operator, $operand2);
	}

	/**
	 * Tests the existence of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_PropertyExistenceInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function propertyExistence($propertyName, $selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058196);
	}

	/**
	 * Performs a full-text search against the specified or default selector.
	 *
	 * @param string $propertyName the property name, or null to search all full-text indexed properties of the node (or node subgraph, in some implementations);
	 * @param string $fullTextSearchExpression the full-text search expression; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_FullTextSearchInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058197);
	}

	/**
	 * Tests whether a node in the specified or default selector is reachable by a specified absolute path.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @param string $path an absolute path; non-null
	 * @return Tx_Extbase_Persistence_QOM_SameNodeInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function sameNode($path, $selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058198);
	}

	/**
	 * Tests whether a node in the specified or default selector is a child of a node reachable by a specified absolute path.
	 *
	 * @param string $path an absolute path; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_ChildNodeInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function childNode($path, $selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058199);
	}

	/**
	 * Tests whether a node in the specified or default selector is a descendant of a node reachable by a specified absolute path.
	 *
	 * @param string $path an absolute path; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_DescendantNodeInterface the constraint; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function descendantNode($path, $selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058200);
	}

	/**
	 * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_PropertyValueInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function propertyValue($propertyName, $selectorName = '') {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_PropertyValue', $propertyName, $selectorName);
	}

	/**
	 * Evaluates to the length (or lengths, if multi-valued) of a property.
	 *
	 * @param Tx_Extbase_Persistence_QOM_PropertyValueInterface $propertyValue the property value for which to compute the length; non-null
	 * @return Tx_Extbase_Persistence_QOM_LengthInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function length(Tx_Extbase_Persistence_QOM_PropertyValueInterface $propertyValue) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058202);
	}

	/**
	 * Evaluates to a NAME value equal to the prefix-qualified name of a node in the specified or default selector.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_NodeNameInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function nodeName($selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058203);
	}

	/**
	 * Evaluates to a NAME value equal to the local (unprefixed) name of a node in the specified or default selector.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_NodeLocalNameInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function nodeLocalName($selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058204);
	}

	/**
	 * Evaluates to a DOUBLE value equal to the full-text search score of a node in the specified or default selector.
	 *
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_FullTextSearchScoreInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function fullTextSearchScore($selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058205);
	}

	/**
	 * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand the operand whose value is converted to a lower-case string; non-null
	 * @return Tx_Extbase_Persistence_QOM_LowerCaseInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
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
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function upperCase(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_UpperCase', $operand);
	}

	/**
	 * Evaluates to the value of a bind variable.
	 *
	 * @param string $bindVariableName the bind variable name; non-null
	 * @return Tx_Extbase_Persistence_QOM_BindVariableValueInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function bindVariable($bindVariableName) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_BindVariableValue', $bindVariableName);
	}

	/**
	 * Evaluates to a literal value.
	 *
	 * The query is invalid if no value is bound to $literalValue.
	 *
	 * @param \F3\PHPCR\ValueInterface $literalValue the value
	 * @return \F3\PHPCR\ValueInterface the operand; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if a particular validity test is possible on this method, the implemention chooses to perform that test (and not leave it until later) on createQuery, and the parameters given fail that test
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function literal(Tx_Extbase_Persistence_ValueInterface $literalValue) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058209);
	}

	/**
	 * Orders by the value of the specified operand, in ascending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand the operand by which to order; non-null
	 * @return Tx_Extbase_Persistence_QOM_OrderingInterface the ordering
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
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
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query is invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function descending(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand) {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_Ordering', $operand, Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING);
	}

	/**
	 * Identifies a property in the specified or default selector to include in
	 * the tabular view of query results.
	 * The column name is the property name if not given.
	 *
	 * The query is invalid if:
	 * $selectorName is not the name of a selector in the query, or
	 * $propertyName is specified but it is not a syntactically valid JCR name, or
	 * $propertyName is specified but does not evaluate to a scalar value, or
	 * $propertyName is specified but $columnName is omitted, or
	 * $propertyName is omitted but $columnName is specified, or
	 * the columns in the tabular view are not uniquely named, whether those
	 * column names are specified by $columnName (if $propertyName is specified)
	 * or generated as described above (if $propertyName is omitted).
	 *
	 * If $propertyName is specified but, for a node-tuple, the selector node
	 * does not have a property named $propertyName, the query is valid and the
	 * column has null value.
	 *
	 * @param string $propertyName the property name, or null to include a column for each single-value non-residual property of the selector's node type
	 * @param string $columnName the column name; must be null if propertyName is null
	 * @param string $selectorName the selector name; non-null
	 * @return Tx_Extbase_Persistence_QOM_ColumnInterface the column; non-null
	 * @throws \F3\PHPCR\Query\InvalidQueryException if the query has no default selector or is otherwise invalid
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the operation otherwise fails
	 */
	public function column($propertyName, $columnName = NULL, $selectorName = NULL) {
		throw new Tx_Extbase_Persistence_Exception('Method not yet implemented, sorry!', 1217058211);
	}

}
?>
