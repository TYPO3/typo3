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

namespace TYPO3\CMS\IndexedSearch\Dto;

/**
 * DTO used for the indexed_search Indexer class
 */
final class IndexingDataAsString
{
    public function __construct(
        public string $title = '',
        public string $body = '',
        public string $keywords = '',
        public string $description = '',
    ) {}

    /**
     * @param array{title?: string|null, body?: string|null, keywords?: string|null, description?: string|null} $input
     */
    public static function fromArray(array $input): IndexingDataAsString
    {
        return new IndexingDataAsString(
            $input['title'] ?? '',
            $input['body'] ?? '',
            $input['keywords'] ?? '',
            $input['description'] ?? '',
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
