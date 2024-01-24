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
 *
 * @internal
 */
final class IndexingDataAsArray
{
    public function __construct(
        public array $title = [],
        public array $body = [],
        public array $keywords = [],
        public array $description = [],
    ) {}

    /**
     * @param array{title?: array|null, body?: array|null, keywords?: array|null, description?: array|null} $input
     */
    public static function fromArray(array $input): IndexingDataAsArray
    {
        return new IndexingDataAsArray(
            $input['title'] ?? [],
            $input['body'] ?? [],
            $input['keywords'] ?? [],
            $input['description'] ?? [],
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
