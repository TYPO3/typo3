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

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\AtImportInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionElseInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeTyposcriptInclude;

/**
 * Create a TypoScript source back from an IncludeTree. Inline source from
 * "@import" and friends.
 *
 * This visitor is used in ext:tstemplate TypoScript modules and ext:backend page TSconfig
 * backend modules to show code of single includes with their resolved imports.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class IncludeTreeSourceAggregatorVisitor implements IncludeTreeVisitorInterface
{
    /**
     * The accumulated source.
     */
    private string $source = '';

    /**
     * Restrict source rendering to specific includes. Used in BE template analyzer
     * to output source of a single include and its sub includes. Since a single include
     * could be included multiple times, we track if source for it has been build to
     * suppress outputting it multiple times.
     */
    private string $startNodeIdentifier = '';
    private bool $startNodeHandled = false;
    private int $startNodeDepth = 0;
    private bool $isWithinStartNode = false;

    public function setStartNodeIdentifier(string $startNodeIdentifier)
    {
        $this->startNodeIdentifier = $startNodeIdentifier;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if ($this->startNodeHandled && $currentDepth <= $this->startNodeDepth) {
            $this->isWithinStartNode = false;
        }
        if ($this->startNodeIdentifier === $include->getIdentifier() && !$this->startNodeHandled) {
            $this->startNodeDepth = $currentDepth;
            $this->isWithinStartNode = true;
            $this->startNodeHandled = true;
        }
        if (empty($this->startNodeIdentifier) || $this->isWithinStartNode) {
            $lineStream = $include->getLineStream();
            if ($lineStream !== null
                && !$lineStream->isEmpty()
                && ($include instanceof ConditionInclude || $include instanceof ConditionElseInclude)
            ) {
                $this->source .= "\n#\n# Condition from '" . $include->getName() . '\' Line ' . $include->getConditionToken()->getLine() . "\n#\n";
                $this->source .= $lineStream;
            }
            if ($include instanceof IncludeTyposcriptInclude || $include instanceof AtImportInclude) {
                $this->source .= "\n#\n# Include from definition '" . trim((string)($include->getOriginalLine()->getTokenStream())) . "'\n#\n";
            }
        }
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        if (empty($this->startNodeIdentifier) || $this->isWithinStartNode) {
            $lineStream = $include->getLineStream();
            if ($lineStream === null
                || $lineStream->isEmpty()
                || ($include->isSplit())
            ) {
                return;
            }
            $this->source .= "\n#\n# Content from '" . $include->getName() . "'\n#\n";
            $this->source .= $lineStream;
        }
    }
}
