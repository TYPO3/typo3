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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeConditionInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;

/**
 * Gather conditions in an IncludeTree.
 *
 * This visitor is used in ext:tstemplate TypoScript modules and ext:backend page TSconfig
 * backend modules to find available conditions and make them toggleable.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class IncludeTreeConditionAggregatorVisitor implements IncludeTreeVisitorInterface
{
    /**
     * @var array<int, array<string, string>>
     */
    private array $conditions = [];

    /**
     * Get accumulated conditions gathered by visit().
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        // No-op. Magic happens in visit()
    }

    /**
     * If the given include is a IncludeConditionInterface, grab it's original (unchanged by constants)
     * condition token.
     */
    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        if (!$include instanceof IncludeConditionInterface) {
            return;
        }
        $condition = $include->getConditionToken()->getValue();
        if (!in_array($condition, array_column($this->conditions, 'value'))) {
            $this->conditions[] = [
                'value' => $condition,
                'originalValue' => $include->getOriginalConditionToken()?->getValue(),
            ];
        }
    }
}
