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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

trait VariablesTrait
{
    public function withRequiredDefinedVariableNames(string ...$variableNames): self
    {
        $target = clone $this;
        $target->requiredDefinedVariableNames = $variableNames;
        return $target;
    }

    private function hasAllRequiredDefinedVariableNames(Variables $variables): bool
    {
        foreach ($this->requiredDefinedVariableNames ?? [] as $variableName) {
            if (!array_key_exists($variableName, $variables)
                || $variables[$variableName] === null
            ) {
                return false;
            }
        }
        return true;
    }
}
