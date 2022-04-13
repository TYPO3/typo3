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

namespace TYPO3\CMS\Core\TypoScript\AST\Traverser;

use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstVisitorInterface;

/**
 * Traverse the entire AST.
 *
 * @internal: Internal AST structure.
 */
final class AstTraverser
{
    /**
     * @var AstVisitorInterface[]
     */
    private array $visitors;

    public function addVisitor(AstVisitorInterface $visitor): void
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

    public function traverse(RootNode $rootNode): void
    {
        $currentObjectPath = new CurrentObjectPath();
        $this->traverseRecursive($rootNode, $rootNode, $currentObjectPath, 0);
    }

    private function traverseRecursive(RootNode $nodeRoot, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        $currentObjectPath->append($node);
        foreach ($this->visitors as $visitor) {
            $visitor->visitBeforeChildren($nodeRoot, $node, $currentObjectPath, $currentDepth);
        }
        foreach ($node->getNextChild() as $child) {
            $this->traverseRecursive($nodeRoot, $child, $currentObjectPath, $currentDepth + 1);
            foreach ($this->visitors as $visitor) {
                $visitor->visit($nodeRoot, $child, $currentObjectPath, $currentDepth);
            }
        }
        foreach ($this->visitors as $visitor) {
            $visitor->visitAfterChildren($nodeRoot, $node, $currentObjectPath, $currentDepth);
        }
        $currentObjectPath->removeLast();
    }
}
