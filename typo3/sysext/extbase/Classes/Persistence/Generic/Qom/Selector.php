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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
 * Selects a subset of the nodes in the repository based on node type.
 *
 * A selector selects every node in the repository, subject to access control
 * constraints, that satisfies at least one of the following conditions:
 *
 * the node's primary node type is nodeType, or
 * the node's primary node type is a subtype of nodeType, or
 * the node has a mixin node type that is nodeType, or
 * the node has a mixin node type that is a subtype of nodeType.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class Selector implements SourceInterface, SelectorInterface
{
    public function __construct(
        private string $selectorName,
        private ?string $nodeTypeName,
    ) {}

    public function getNodeTypeName(): ?string
    {
        return $this->nodeTypeName;
    }

    public function getSelectorName(): string
    {
        return $this->selectorName;
    }
}
