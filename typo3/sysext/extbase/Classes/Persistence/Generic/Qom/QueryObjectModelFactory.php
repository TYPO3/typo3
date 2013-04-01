<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * The Query Object Model Factory
 */
class QueryObjectModelFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Selects a subset of the nodes in the repository based on node type.
	 *
	 * @param string $nodeTypeName the name of the required node type; non-null
	 * @param string $selectorName the selector name; optional
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface the selector
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function selector($nodeTypeName, $selectorName = '') {
		if ($selectorName === '') {
			$selectorName = $nodeTypeName;
		}
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Selector', $selectorName, $nodeTypeName);
	}

	/**
	 * Sets a statement as constraint. This is not part of the JCR 2.0 Specification!
	 *
	 * @param string $statement The statement
	 * @param array $boundVariables An array of variables to bind to the statement
	 * @param object|string $language The language of the statement. Must be a supported languanguage defined as \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory::TYPO3_*
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement
	 */
	public function statement($statement, array $boundVariables = array(), $language = \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement::TYPO3_SQL_MYSQL) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement', $statement, $boundVariables, $language);
	}

	/**
	 * Performs a join between two node-tuple sources.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $left the left node-tuple source; non-null
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $right the right node-tuple source; non-null
	 * @param string $joinType one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
	 * @param JoinConditionInterface $joinCondition
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface the join; non-null
	 */
	public function join(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $left, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $right, $joinType, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinConditionInterface $joinCondition) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Join', $left, $right, $joinType, $joinCondition);
	}

	/**
	 * Tests whether the value of a property in a first selector is equal to the value of a property in a second selector.
	 *
	 * @param string $selector1Name the name of the first selector; non-null
	 * @param string $property1Name the property name in the first selector; non-null
	 * @param string $selector2Name the name of the second selector; non-null
	 * @param string $property2Name the property name in the second selector; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\EquiJoinConditionInterface the constraint; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\EquiJoinCondition', $selector1Name, $property1Name, $selector2Name, $property2Name);
	}

	/**
	 * Performs a logical conjunction of two other constraints.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint1 the first constraint; non-null
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint2 the second constraint; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface the And constraint; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function _and(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint1, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint2) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalAnd', $constraint1, $constraint2);
	}

	/**
	 * Performs a logical disjunction of two other constraints.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint1 the first constraint; non-null
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint2 the second constraint; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface the Or constraint; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function _or(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint1, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint2) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalOr', $constraint1, $constraint2);
	}

	/**
	 * Performs a logical negation of another constraint.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint the constraint to be negated; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface the Not constraint; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function not(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LogicalNot', $constraint);
	}

	/**
	 * Filters node-tuples based on the outcome of a binary operation.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand1 the first operand; non-null
	 * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\StaticOperandInterface $operand2 the second operand; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface the constraint; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function comparison(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand1, $operator, $operand2) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Comparison', $operand1, $operator, $operand2);
	}

	/**
	 * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
	 *
	 * @param string $propertyName the property name; non-null
	 * @param string $selectorName the selector name; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValueInterface the operand; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function propertyValue($propertyName, $selectorName = '') {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\PropertyValue', $propertyName, $selectorName);
	}

	/**
	 * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand the operand whose value is converted to a lower-case string; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\LowerCaseInterface the operand; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function lowerCase(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\LowerCase', $operand);
	}

	/**
	 * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand the operand whose value is converted to a upper-case string; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\UpperCaseInterface the operand; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function upperCase(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\UpperCase', $operand);
	}

	/**
	 * Orders by the value of the specified operand, in ascending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand the operand by which to order; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrderingInterface the ordering
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function ascending(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Ordering', $operand, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING);
	}

	/**
	 * Orders by the value of the specified operand, in descending order.
	 *
	 * The query is invalid if $operand does not evaluate to a scalar value.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand the operand by which to order; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrderingInterface the ordering
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function descending(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Ordering', $operand, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING);
	}

	/**
	 * Evaluates to the value of a bind variable.
	 *
	 * @param string $bindVariableName the bind variable name; non-null
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\BindVariableValueInterface the operand; non-null
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
	 */
	public function bindVariable($bindVariableName) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\BindVariableValue', $bindVariableName);
	}
}

?>