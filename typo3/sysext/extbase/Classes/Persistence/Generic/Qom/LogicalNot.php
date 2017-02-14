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
 * Performs a logical negation of another constraint.
 *
 * To satisfy the Not constraint, the node-tuple must not satisfy constraint.
 */
class LogicalNot implements NotInterface
{
    /**
     * @var ConstraintInterface
     */
    protected $constraint;

    /**
     * @param ConstraintInterface $constraint
     */
    public function __construct(ConstraintInterface $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * Fills an array with the names of all bound variables in the constraint
     *
     * @param array &$boundVariables
     */
    public function collectBoundVariableNames(&$boundVariables)
    {
        $this->constraint->collectBoundVariableNames($boundVariables);
    }

    /**
     * Gets the constraint negated by this Not constraint.
     *
     * @return ConstraintInterface the constraint; non-null
     */
    public function getConstraint()
    {
        return $this->constraint;
    }
}
