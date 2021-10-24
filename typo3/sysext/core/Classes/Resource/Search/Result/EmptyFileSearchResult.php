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
    /**
     * @return int
     * @see Countable::count()
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * @see Iterator::current()
     * @todo change return type to mixed in TYPO3 v12 (breaking).
     */
    #[\ReturnTypeWillChange]
    public function current(): void
    {
        // Noop
    }

    /**
     * @see Iterator::key()
     * @todo change return type to mixed in TYPO3 v12 (breaking).
     */
    #[\ReturnTypeWillChange]
    public function key(): void
    {
        // Noop
    }

    /**
     * @see Iterator::next()
     */
    public function next(): void
    {
        // Noop
    }

    /**
     * @see Iterator::rewind()
     */
    public function rewind(): void
    {
        // Noop
    }

    /**
     * @return bool
     * @see Iterator::valid()
     */
    public function valid(): bool
    {
        return false;
    }
}
