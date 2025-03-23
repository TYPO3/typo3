<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * The Query Object Model Factory
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class QueryObjectModelFactory implements SingletonInterface
{
    /**
     * Selects a subset of the nodes in the repository based on node type.
     */
    public function selector(?string $nodeTypeName = null, string $selectorName = ''): SourceInterface&SelectorInterface
    {
        if ($selectorName === '') {
            $selectorName = $nodeTypeName;
        }
        return new Selector($selectorName, $nodeTypeName);
    }

    /**
     * Sets a statement as constraint. This is not part of the JCR 2.0 Specification!
     */
    public function statement(string $statement, array $boundVariables = []): Statement
    {
        return GeneralUtility::makeInstance(Statement::class, $statement, $boundVariables);
    }

    /**
     * Performs a join between two node-tuple sources.
     */
    public function join(
        SourceInterface&SelectorInterface $left,
        SourceInterface&SelectorInterface $right,
        string $joinType,
        JoinConditionInterface $joinCondition
    ): SourceInterface&JoinInterface {
        return new Join($left, $right, $joinType, $joinCondition);
    }

    /**
     * Tests whether the value of a property in a first selector is equal to the value of a property in a second selector.
     */
    public function equiJoinCondition(string $selector1Name, string $property1Name, string $selector2Name, string $property2Name): EquiJoinConditionInterface
    {
        return GeneralUtility::makeInstance(EquiJoinCondition::class, $selector1Name, $property1Name, $selector2Name, $property2Name);
    }

    /**
     * Performs a logical conjunction of two other constraints.
     */
    public function _and(ConstraintInterface $constraint1, ConstraintInterface $constraint2): AndInterface
    {
        return GeneralUtility::makeInstance(LogicalAnd::class, $constraint1, $constraint2);
    }

    /**
     * Performs a logical disjunction of two other constraints.
     */
    public function _or(ConstraintInterface $constraint1, ConstraintInterface $constraint2): OrInterface
    {
        return GeneralUtility::makeInstance(LogicalOr::class, $constraint1, $constraint2);
    }

    /**
     * Performs a logical negation of another constraint.
     */
    public function not(ConstraintInterface $constraint): NotInterface
    {
        return GeneralUtility::makeInstance(LogicalNot::class, $constraint);
    }

    /**
     * Filters node-tuples based on the outcome of a binary operation.
     */
    public function comparison(PropertyValueInterface $operand1, int $operator, mixed $operand2): ComparisonInterface
    {
        return GeneralUtility::makeInstance(Comparison::class, $operand1, $operator, $operand2);
    }

    /**
     * Evaluates to the value (or values, if multi-valued) of a property in the specified or default selector.
     */
    public function propertyValue(string $propertyName, string $selectorName = ''): PropertyValueInterface
    {
        return GeneralUtility::makeInstance(PropertyValue::class, $propertyName, $selectorName);
    }

    /**
     * Evaluates to the lower-case string value (or values, if multi-valued) of an operand.
     */
    public function lowerCase(PropertyValueInterface $operand): LowerCaseInterface
    {
        return GeneralUtility::makeInstance(LowerCase::class, $operand);
    }

    /**
     * Evaluates to the upper-case string value (or values, if multi-valued) of an operand.
     */
    public function upperCase(PropertyValueInterface $operand): UpperCaseInterface
    {
        return GeneralUtility::makeInstance(UpperCase::class, $operand);
    }

    /**
     * Orders by the value of the specified operand, in ascending order.
     *
     * The query is invalid if $operand does not evaluate to a scalar value.
     */
    public function ascending(DynamicOperandInterface $operand): OrderingInterface
    {
        return GeneralUtility::makeInstance(Ordering::class, $operand, QueryInterface::ORDER_ASCENDING);
    }

    /**
     * Orders by the value of the specified operand, in descending order.
     *
     * The query is invalid if $operand does not evaluate to a scalar value.
     */
    public function descending(DynamicOperandInterface $operand): OrderingInterface
    {
        return GeneralUtility::makeInstance(Ordering::class, $operand, QueryInterface::ORDER_DESCENDING);
    }

    /**
     * Evaluates to the value of a bind variable.
     */
    public function bindVariable(string $bindVariableName): BindVariableValueInterface
    {
        return GeneralUtility::makeInstance(BindVariableValue::class, $bindVariableName);
    }
}
