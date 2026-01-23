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
 * Base interface for SQL function expressions with multiple operands.
 *
 * Function expressions can be used in ORDER BY clauses to sort results
 * by computed values such as CONCAT, TRIM, COALESCE, etc.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
interface FunctionExpressionInterface extends DynamicOperandInterface
{
    /**
     * Returns the operands of this function expression.
     *
     * @return array<DynamicOperandInterface|string> The operands
     */
    public function getOperands(): array;

    /**
     * Returns the SQL function name.
     *
     * @return string The function name (e.g., 'CONCAT', 'TRIM', 'COALESCE')
     */
    public function getFunctionName(): string;
}
