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

use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\ConditionMatcherInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeConditionInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;

/**
 * A visitor that looks at IncludeConditionInterface nodes and
 * evaluates their conditions.
 *
 * Condition matching is done in visitBeforeChildren() to be used in combination with
 * ConditionVerdictAwareIncludeTreeTraverser, so children are only traversed for
 * conditions that evaluated true.
 *
 * @internal: Internal tree structure.
 */
final class IncludeTreeConditionMatcherVisitor implements IncludeTreeVisitorInterface
{
    private ConditionMatcherInterface $conditionMatcher;

    public function setConditionMatcher(ConditionMatcherInterface $conditionMatcher)
    {
        $this->conditionMatcher = $conditionMatcher;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if (!$include instanceof IncludeConditionInterface) {
            return;
        }
        $conditionValue = $include->getConditionToken()->getValue();
        // @todo: This bracket handling is stupid, it's removed in matcher again ...
        $verdict = $this->conditionMatcher->match('[' . $conditionValue . ']');
        $include->setConditionVerdict($verdict);
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
    }
}
