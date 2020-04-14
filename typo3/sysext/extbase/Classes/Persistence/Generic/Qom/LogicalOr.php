<?php

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

/**
 * Performs a logical disjunction of two other constraints.
 *
 * To satisfy the Or constraint, the node-tuple must either:
 * satisfy constraint1 but not constraint2, or
 * satisfy constraint2 but not constraint1, or
 * satisfy both constraint1 and constraint2.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class LogicalOr implements OrInterface
{
    /**
     * @var ConstraintInterface
     */
    protected $constraint1;

    /**
     * @var ConstraintInterface
     */
    protected $constraint2;

    /**
     * @param ConstraintInterface $constraint1
     * @param ConstraintInterface $constraint2
     */
    public function __construct(ConstraintInterface $constraint1, ConstraintInterface $constraint2)
    {
        $this->constraint1 = $constraint1;
        $this->constraint2 = $constraint2;
    }

    /**
     * Fills an array with the names of all bound variables in the constraints
     *
     * @param array $boundVariables
     */
    public function collectBoundVariableNames(&$boundVariables)
    {
        $this->constraint1->collectBoundVariableNames($boundVariables);
        $this->constraint2->collectBoundVariableNames($boundVariables);
    }

    /**
     * Gets the first constraint.
     *
     * @return ConstraintInterface the constraint; non-null
     */
    public function getConstraint1()
    {
        return $this->constraint1;
    }

    /**
     * Gets the second constraint.
     *
     * @return ConstraintInterface the constraint; non-null
     */
    public function getConstraint2()
    {
        return $this->constraint2;
    }
}
