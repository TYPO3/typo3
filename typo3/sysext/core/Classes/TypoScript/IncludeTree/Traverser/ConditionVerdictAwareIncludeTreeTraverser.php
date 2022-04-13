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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeConditionInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeVisitorInterface;

/**
 * An optimized traverser that does not traverse children when a node is
 * a condition node that evaluate false.
 *
 * This is pretty clever: When adding the ConditionMatcherVisitor as first visitor, it
 * sets the condition verdict of a ConditionInterface node in visitBeforeChildren().
 * Adding the AstBuilderVisitor as second visitor, the AstBuilderVisitor will not be
 * called for ConditionInterface children that did not evaluate to true.
 * This way, we can both evaluate conditions and build the AST in only one traversing round.
 *
 * @internal: Internal tree structure.
 */
final class ConditionVerdictAwareIncludeTreeTraverser implements IncludeTreeTraverserInterface
{
    /**
     * @var IncludeTreeVisitorInterface[]
     */
    private array $visitors;

    public function addVisitor(IncludeTreeVisitorInterface $visitor): void
    {
        $this->visitors[] = $visitor;
    }

    /**
     * Reset to re-use traverser multiple times with different set of visitors.
     */
    public function resetVisitors(): void
    {
        $this->visitors = [];
    }

    public function traverse(RootInclude $rootInclude): void
    {
        $this->traverseRecursive($rootInclude, 0);
    }

    private function traverseRecursive(IncludeInterface $include, int $currentDepth): void
    {
        foreach ($this->visitors as $visitor) {
            $visitor->visitBeforeChildren($include, $currentDepth);
        }
        if ($include instanceof IncludeConditionInterface && !$include->getConditionVerdict()) {
            // Don't traverse children if condition did not match.
            return;
        }
        foreach ($include->getNextChild() as $child) {
            $this->traverseRecursive($child, $currentDepth + 1);
            foreach ($this->visitors as $visitor) {
                $visitor->visit($child, $currentDepth);
            }
        }
    }
}
