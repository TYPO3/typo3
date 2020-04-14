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
 * Performs a logical negation of another constraint.
 *
 * To satisfy the Not constraint, the node-tuple must not satisfy constraint.
 */
interface NotInterface extends ConstraintInterface
{
    /**
     * Gets the constraint negated by this Not constraint.
     *
     * @return ConstraintInterface the constraint; non-null
     */
    public function getConstraint();
}
