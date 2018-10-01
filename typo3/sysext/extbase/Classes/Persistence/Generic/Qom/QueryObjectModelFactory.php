<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * The Query Object Model Factory
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class QueryObjectModelFactory implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
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
    public function selector($nodeTypeName, $selectorName = '')
    {
        if ($selectorName === '') {
            $selectorName = $nodeTypeName;
        }
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector::class, $selectorName, $nodeTypeName);
    }

    /**
     * Sets a statement as constraint. This is not part of the JCR 2.0 Specification!
     *
     * @param string $statement The statement
     * @param array $boundVariables An array of variables to bind to the statement
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement
     */
    public function statement($statement, array $boundVariables = [])
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement::class, $statement, $boundVariables);
    }

    /**
     * Performs a join between two node-tuple sources.
     *
     * @param SourceInterface $left the left node-tuple source; non-null
     * @param SourceInterface $right the right node-tuple source; non-null
     * @param string $joinType one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
     * @param JoinConditionInterface $joinCondition
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface the join; non-null
     */
    public function join(SourceInterface $left, SourceInterface $right, $joinType, JoinConditionInterface $joinCondition)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\Join::class, $left, $right, $joinType, $joinCondition);
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
    public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\EquiJoinCondition::class, $selector1Name, $property1Name, $selector2Name, $property2Name);
    }

    /**
     * Performs a logical conjunction of two other constraints.
     *
     * @param ConstraintInterface $constraint1 the first constraint; non-null
     * @param ConstraintInterface $constraint2 the second constraint; non-null
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface the And constraint; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function _and(ConstraintInterface $constraint1, ConstraintInterface $constraint2)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\LogicalAnd::class, $constraint1, $constraint2);
    }

    /**
     * Performs a logical disjunction of two other constraints.
     *
     * @param ConstraintInterface $constraint1 the first constraint; non-null
     * @param ConstraintInterface $constraint2 the second constraint; non-null
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface the Or constraint; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function _or(ConstraintInterface $constraint1, ConstraintInterface $constraint2)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\LogicalOr::class, $constraint1, $constraint2);
    }

    /**
     * Performs a logical negation of another constraint.
     *
     * @param ConstraintInterface $constraint the constraint to be negated; non-null
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface the Not constraint; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function not(ConstraintInterface $constraint)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\LogicalNot::class, $constraint);
    }

    /**
     * Filters node-tuples based on the outcome of a binary operation.
     *
     * @param PropertyValueInterface $operand1 the first operand; non-null
     * @param string $operator the operator; one of QueryObjectModelConstants.JCR_OPERATOR_*
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\StaticOperandInterface $operand2 the second operand; non-null
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface the constraint; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function comparison(PropertyValueInterface $operand1, $operator, $operand2)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\Comparison::class, $operand1, $operator, $operand2);
    }

    /**
     * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
     *
     * @param string $propertyName the property name; non-null
     * @param string $selectorName the selector name; non-null
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValueInterface the operand; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function propertyValue($propertyName, $selectorName = '')
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValue::class, $propertyName, $selectorName);
    }

    /**
     * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
     *
     * @param PropertyValueInterface $operand the operand whose value is converted to a lower-case string; non-null
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\LowerCaseInterface the operand; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function lowerCase(PropertyValueInterface $operand)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\LowerCase::class, $operand);
    }

    /**
     * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
     *
     * @param PropertyValueInterface $operand the operand whose value is converted to an upper-case string; non-null
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\UpperCaseInterface the operand; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function upperCase(PropertyValueInterface $operand)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\UpperCase::class, $operand);
    }

    /**
     * Orders by the value of the specified operand, in ascending order.
     *
     * The query is invalid if $operand does not evaluate to a scalar value.
     *
     * @param DynamicOperandInterface $operand the operand by which to order; non-null
     * @return OrderingInterface the ordering
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function ascending(DynamicOperandInterface $operand)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\Ordering::class, $operand, \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING);
    }

    /**
     * Orders by the value of the specified operand, in descending order.
     *
     * The query is invalid if $operand does not evaluate to a scalar value.
     *
     * @param DynamicOperandInterface $operand the operand by which to order; non-null
     * @return OrderingInterface the ordering
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function descending(DynamicOperandInterface $operand)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\Ordering::class, $operand, \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING);
    }

    /**
     * Evaluates to the value of a bind variable.
     *
     * @param string $bindVariableName the bind variable name; non-null
     * @return BindVariableValueInterface the operand; non-null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException if the operation otherwise fails
     */
    public function bindVariable($bindVariableName)
    {
        return $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\BindVariableValue::class, $bindVariableName);
    }
}
