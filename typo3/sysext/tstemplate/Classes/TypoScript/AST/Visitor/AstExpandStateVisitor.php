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
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstVisitorInterface;

/**
 * Apply backend user expand / collapse state to AST. Used in Object Browser.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 * @todo: The current strategy iterating $flatCurrentObjectPath is rather expensive
 *        and slows the backend module down for big trees. Find something less expensive.
 */
final class AstExpandStateVisitor implements AstVisitorInterface
{
    private ?string $expandPath;
    private ?string $collapsePath;
    private array $storedExpands;

    public function setToExpandPath(string $toExpandPath)
    {
        $this->expandPath = $toExpandPath;
    }

    public function setToCollapsePath(string $toCollapsePath)
    {
        $this->collapsePath = $toCollapsePath;
    }

    public function setStoredExpands(array $storedExpands)
    {
        $this->storedExpands = $storedExpands;
    }

    public function getStoredExpands(): array
    {
        return $this->storedExpands;
    }

    public function visitBeforeChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        $flatCurrentObjectPath = $currentObjectPath->getPathAsString();
        if ($this->collapsePath && str_starts_with($flatCurrentObjectPath, $this->collapsePath)) {
            foreach ($this->storedExpands as $key => $storedExpand) {
                if (str_starts_with($storedExpand, $this->collapsePath)) {
                    unset($this->storedExpands[$key]);
                }
            }
        }
        if ($this->expandPath && str_starts_with($this->expandPath, $flatCurrentObjectPath)) {
            if (!in_array($this->expandPath, $this->storedExpands)) {
                $this->storedExpands[] = $this->expandPath;
            }
            $node->setExpanded(true);
        }
        if (in_array($flatCurrentObjectPath, $this->storedExpands)) {
            $node->setExpanded(true);
        }
    }

    public function visit(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
    }

    public function visitAfterChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
    }
}
