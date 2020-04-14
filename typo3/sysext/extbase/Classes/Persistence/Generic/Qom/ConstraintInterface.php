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
 * Filters the set of tuples formed by evaluating the query's sources and
 * the joins between them.
 *
 * To be included in the query results, a tuple must satisfy the constraint.
 */
interface ConstraintInterface
{
    /**
     * Fills an array with the names of all bound variables in the constraints
     *
     * @param array $boundVariables
     */
    public function collectBoundVariableNames(&$boundVariables);
}
