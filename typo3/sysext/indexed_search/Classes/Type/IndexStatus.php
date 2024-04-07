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

namespace TYPO3\CMS\IndexedSearch\Type;

/**
 * @internal
 */
enum IndexStatus
{
    case MTIME_MATCHED;
    case MINIMUM_AGE_NOT_EXCEEDED;
    case MODIFICATION_TIME_DIFFERS;
    case MODIFICATION_TIME_NOT_SET;
    case NEW_DOCUMENT;

    public function reason(): string
    {
        return match ($this) {
            self::MTIME_MATCHED => 'mtime matched the document, so no changes detected and no content updated',
            self::MINIMUM_AGE_NOT_EXCEEDED => 'The minimum age was not exceeded',
            self::MODIFICATION_TIME_DIFFERS => 'The minimum age was exceed and mtime was set and the mtime was different, so the page was indexed',
            self::MODIFICATION_TIME_NOT_SET => 'The minimum age was exceed, but mtime was not set, so the page was indexed',
            self::NEW_DOCUMENT => 'Page has never been indexed (is not represented in the index_phash table)',
        };
    }

    public function reindexRequired(): bool
    {
        return match ($this) {
            self::MTIME_MATCHED, self::MINIMUM_AGE_NOT_EXCEEDED => false,
            default => true,
        };
    }
}
