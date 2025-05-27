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

namespace TYPO3\CMS\Backend\Breadcrumb;

use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Represents a breadcrumb context with the main entity and optional suffix nodes.
 *
 * A breadcrumb context consists of:
 * - A main context (record or resource) that determines the base breadcrumb trail
 * - Optional suffix nodes that are appended after the main trail
 *
 * Suffix nodes are useful for:
 * - "New Record" indicators when creating records
 * - "Edit Multiple" indicators when editing multiple records
 * - Custom action indicators
 *
 * Example usage:
 *
 *     // Edit existing record
 *     $context = new BreadcrumbContext($record, []);
 *
 *     // Create new record (shows: Pages > Parent Page > "Create New Content")
 *     $suffixNode = new BreadcrumbNode(identifier: 'new', label: 'Create New Content');
 *     $context = new BreadcrumbContext($parentPage, [$suffixNode]);
 *
 * @internal Subject to change until v15 LTS
 */
final readonly class BreadcrumbContext
{
    /**
     * @param RecordInterface|ResourceInterface|null $mainContext The main entity (record or resource)
     * @param BreadcrumbNode[] $suffixNodes Additional nodes to append after the main breadcrumb trail
     */
    public function __construct(
        public RecordInterface|ResourceInterface|null $mainContext,
        public array $suffixNodes = [],
    ) {}

    /**
     * Checks if this context has a valid main entity.
     */
    public function hasContext(): bool
    {
        return $this->mainContext !== null;
    }

    /**
     * Checks if this context has suffix nodes.
     */
    public function hasSuffixNodes(): bool
    {
        return $this->suffixNodes !== [];
    }
}
