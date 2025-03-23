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

/**
 * Performs a logical disjunction of two other constraints.
 *
 * To satisfy the Or constraint, the node-tuple must either:
 * satisfy constraint1 but not constraint2, or
 * satisfy constraint2 but not constraint1, or
 * satisfy both constraint1 and constraint2.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class LogicalOr implements OrInterface
{
    public function __construct(
        protected ConstraintInterface $constraint1,
        protected ConstraintInterface $constraint2
    ) {}

    public function collectBoundVariableNames(array &$boundVariables): void
    {
        $this->constraint1->collectBoundVariableNames($boundVariables);
        $this->constraint2->collectBoundVariableNames($boundVariables);
    }

    public function getConstraint1(): ConstraintInterface
    {
        return $this->constraint1;
    }

    public function getConstraint2(): ConstraintInterface
    {
        return $this->constraint2;
    }
}
