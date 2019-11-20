<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching;

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
 * Used for TypoScript Conditions to be evaluated.
 */
interface ConditionMatcherInterface
{
    /**
     * Matches a TypoScript condition expression.
     *
     * @param string $expression The expression to match
     * @return bool Whether the expression matched
     */
    public function match($expression): bool;
}
