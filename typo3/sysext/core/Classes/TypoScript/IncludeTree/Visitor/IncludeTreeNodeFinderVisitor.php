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

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;

/**
 * Find a single node in tree identified by node identifier.
 *
 * This visitor is used in ext:tstemplate TypoScript modules and ext:backend page TSconfig
 * backend modules to find single nodes, for instance when their source should be rendered.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class IncludeTreeNodeFinderVisitor implements IncludeTreeVisitorInterface
{
    private ?IncludeInterface $foundNode = null;
    private string $nodeIdentifier;

    public function setNodeIdentifier(string $nodeIdentifier)
    {
        $this->nodeIdentifier = $nodeIdentifier;
    }

    public function getFoundNode(): ?IncludeInterface
    {
        return $this->foundNode;
    }

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if ($include->getIdentifier() === $this->nodeIdentifier) {
            $this->foundNode = $include;
        }
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        // Implement interface
    }
}
