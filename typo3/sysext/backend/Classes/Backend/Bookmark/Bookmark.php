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
 * Value object representing a bookmark in the backend.
 *
 * Bookmarks provide quick access to frequently used backend locations
 * and are displayed in the bookmark toolbar dropdown.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
readonly class Bookmark implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $route,
        public string $arguments,
        public string $title,
        public int|string $groupId,
        public string $iconIdentifier,
        public string $iconOverlayIdentifier,
        public string $module,
        public string $href,
        public bool $editable = false,
        public bool $accessible = false,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'route' => $this->route,
            'arguments' => $this->arguments,
            'title' => $this->title,
            'groupId' => $this->groupId,
            'iconIdentifier' => $this->iconIdentifier,
            'iconOverlayIdentifier' => $this->iconOverlayIdentifier,
            'module' => $this->module,
            'href' => $this->href,
            'editable' => $this->editable,
            'accessible' => $this->accessible,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
