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

namespace TYPO3\CMS\Core\Resource\Search\Result;

/**
 * Represents an empty search result (no matches found)
 */
class EmptyFileSearchResult implements FileSearchResultInterface
{
    public function count(): int
    {
        return 0;
    }

    /**
     * @phpstan-return null
     */
    public function current(): mixed
    {
        // Noop
        return null;
    }

    /**
     * @phpstan-return null
     */
    public function key(): mixed
    {
        // Noop
        return null;
    }

    public function next(): void
    {
        // Noop
    }

    public function rewind(): void
    {
        // Noop
    }

    /**
     * @return false
     */
    public function valid(): bool
    {
        return false;
    }
}
