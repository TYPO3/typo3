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

namespace TYPO3\CMS\Backend\Backend\Bookmark;

/**
 * Value object representing a bookmark group.
 *
 * Groups are used to organize bookmarks in the backend toolbar.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
readonly class BookmarkGroup implements \JsonSerializable
{
    public function __construct(
        public int|string $id,
        public string $label,
        public BookmarkGroupType $type,
        public int $sorting = 0,
        public bool $editable = false,
        public bool $selectable = true,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type->value,
            'priority' => $this->type->getPriority(),
            'sorting' => $this->sorting,
            'editable' => $this->editable,
            'selectable' => $this->selectable,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
