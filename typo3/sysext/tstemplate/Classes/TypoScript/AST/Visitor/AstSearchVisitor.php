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

namespace TYPO3\CMS\Tstemplate\TypoScript\AST\Visitor;

use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\ReferenceChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstVisitorInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\ConstantAwareTokenStream;

/**
 * Match a string in name or value of a node. Used in Object Browser "search" functionality.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class AstSearchVisitor implements AstVisitorInterface
{
    private string $searchValue;
    private bool $searchInConstants = false;
    private array $storedExpands = [];

    public function setSearchValue(string $searchValue): void
    {
        $this->searchValue = $searchValue;
    }

    /**
     * Set when "Display constants substitutions" is enabled.
     * This allows searching for constant usages in setup.
     */
    public function enableSearchInConstants(): void
    {
        $this->searchInConstants = true;
    }

    public function getStoredExpands(): array
    {
        return $this->storedExpands;
    }

    public function visitBeforeChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
    }

    public function visit(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        $name = $node->getName();
        $value = $node->getValue();
        if ($name && mb_strpos($name, $this->searchValue) !== false) {
            // Match in name
            $node->setExpanded(true);
            $node->setSearchMatchInName();
            $this->storedExpands[] = $currentObjectPath->getPathAsString();
            return;
        }
        if ($value && mb_strpos($value, $this->searchValue) !== false) {
            // Match in value
            $node->setExpanded(true);
            $node->setSearchMatchInValue();
            $this->storedExpands[] = $currentObjectPath->getPathAsString();
        }
        if ($node instanceof ReferenceChildNode
            && mb_strpos((string)$node->getReferenceSourceStream(), $this->searchValue) !== false
        ) {
            $node->setExpanded(true);
            $node->setSearchMatchInValue();
            $this->storedExpands[] = $currentObjectPath->getPathAsString();
        }
        $originalValueTokenStream = $node->getOriginalValueTokenStream();
        if ($this->searchInConstants
            && $originalValueTokenStream instanceof ConstantAwareTokenStream
            && mb_strpos((string)$originalValueTokenStream, $this->searchValue) !== false
        ) {
            // Match in constant
            $node->setExpanded(true);
            $node->setSearchMatchInValue();
            $this->storedExpands[] = $currentObjectPath->getPathAsString();
        }
    }

    public function visitAfterChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        foreach ($node->getNextChild() as $child) {
            if ($child->isExpanded()) {
                $node->setExpanded(true);
                $this->storedExpands[] = $currentObjectPath->getPathAsString();
                break;
            }
        }
    }
}
