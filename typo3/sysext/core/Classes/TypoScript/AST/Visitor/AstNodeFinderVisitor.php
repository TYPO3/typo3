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

namespace TYPO3\CMS\Core\TypoScript\AST\Visitor;

use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;

/**
 * Find a single node in tree identified by node identifier.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class AstNodeFinderVisitor implements AstVisitorInterface
{
    private string $nodeIdentifier;
    private ?NodeInterface $foundNode = null;
    private ?CurrentObjectPath $foundNodeCurrentObjectPath = null;

    public function setNodeIdentifier(string $nodeIdentifier)
    {
        $this->nodeIdentifier = $nodeIdentifier;
    }

    public function getFoundNode(): ?NodeInterface
    {
        return $this->foundNode;
    }

    public function getFoundNodeCurrentObjectPath(): ?CurrentObjectPath
    {
        return $this->foundNodeCurrentObjectPath;
    }

    public function visitBeforeChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        if ($node->getIdentifier() === $this->nodeIdentifier) {
            $this->foundNode = $node;
            $this->foundNodeCurrentObjectPath = clone $currentObjectPath;
        }
    }

    public function visit(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        // Implement interface
    }

    public function visitAfterChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        // Implement interface
    }
}
