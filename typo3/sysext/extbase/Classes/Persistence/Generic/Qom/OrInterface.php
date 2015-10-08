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
 * Performs a logical disjunction of two other constraints.
 *
 * To satisfy the Or constraint, the node-tuple must either:
 * satisfy constraint1 but not constraint2, or
 * satisfy constraint2 but not constraint1, or
 * satisfy both constraint1 and constraint2.
 */
interface OrInterface extends ConstraintInterface
{
    /**
     * Gets the first constraint.
     *
     * @return ConstraintInterface the constraint; non-null
     */
    public function getConstraint1();

    /**
     * Gets the second constraint.
     *
     * @return ConstraintInterface the constraint; non-null
     */
    public function getConstraint2();
}
