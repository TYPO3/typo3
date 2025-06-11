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

namespace TYPO3\CMS\Core\Context;

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The aspect contains whether to show hidden pages, records (content) or even deleted records.
 *
 * Allowed properties:
 * - includeHiddenPages
 * - includeHiddenContent
 * - includeDeletedRecords
 * - includeScheduledRecords
 */
final readonly class VisibilityAspect implements AspectInterface
{
    /**
     * @param bool $includeHiddenPages whether to include hidden=1 in pages tables
     * @param bool $includeHiddenContent whether to include hidden=1 in tables except for pages
     * @param bool $includeScheduledRecords whether to ignore access time in tables
     * @param bool $includeDeletedRecords whether to include deleted=1 records (only for use in recycler)
     */
    public function __construct(
        private bool $includeHiddenPages = false,
        private bool $includeHiddenContent = false,
        private bool $includeDeletedRecords = false,
        private bool $includeScheduledRecords = false,
    ) {}

    /**
     * Fetch the values
     *
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name): bool
    {
        switch ($name) {
            case 'includeHiddenPages':
                return $this->includeHiddenPages;
            case 'includeHiddenContent':
                return $this->includeHiddenContent;
            case 'includeDeletedRecords':
                return $this->includeDeletedRecords;
            case 'includeScheduledRecords':
                return $this->includeScheduledRecords;
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1527780439);
    }

    public function includeHidden(): bool
    {
        return $this->includeHiddenContent || $this->includeHiddenPages;
    }

    public function includeHiddenPages(): bool
    {
        return $this->includeHiddenPages;
    }

    public function includeHiddenContent(): bool
    {
        return $this->includeHiddenContent;
    }

    public function includeScheduledRecords(): bool
    {
        return $this->includeScheduledRecords;
    }

    public function includeDeletedRecords(): bool
    {
        return $this->includeDeletedRecords;
    }
}
