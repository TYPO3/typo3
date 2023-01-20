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

namespace TYPO3\CMS\Filelist\Dto;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class ResourceView
{
    public ?string $moduleUri;
    public ?string $editContentUri;
    public ?string $editDataUri;
    public ?string $replaceUri;
    public ?string $renameUri;

    public function __construct(
        public readonly ResourceInterface $resource,
        public readonly UserPermissions $userPermissions,
        public readonly Icon $icon
    ) {
    }

    public function getUid(): ?int
    {
        if ($this->resource instanceof File) {
            return $this->resource->getUid();
        }

        return null;
    }

    public function getIdentifier(): string
    {
        return $this->resource->getStorage()->getUid() . ':' . $this->resource->getIdentifier();
    }

    public function getMetaDataUid(): ?int
    {
        if ($this->resource instanceof File &&
            $this->resource->isIndexed() &&
            $this->resource->checkActionPermission('editMeta') &&
            $this->userPermissions->editMetaData) {
            return (int)$this->resource->getMetaData()->offsetGet('uid');
        }

        return null;
    }

    public function getType(): string
    {
        if ($this->resource instanceof Folder) {
            return 'folder';
        }
        if ($this->resource instanceof File) {
            return 'file';
        }

        return 'resource';
    }

    public function getName(): string
    {
        if ($this->resource instanceof Folder) {
            return ListUtility::resolveSpecialFolderName($this->resource);
        }

        return $this->resource->getName();
    }

    public function getPreview(): ?File
    {
        if ($this->resource instanceof File
            && ($this->resource->isImage() || $this->resource->isMediaFile())
        ) {
            return $this->resource;
        }

        return null;
    }

    public function getIconSmall(): Icon
    {
        $icon = clone $this->icon;
        $icon->setSize(Icon::SIZE_SMALL);

        return $icon;
    }

    public function getIconMedium(): Icon
    {
        $icon = clone $this->icon;
        $icon->setSize(Icon::SIZE_MEDIUM);

        return $icon;
    }

    public function getIconLarge(): Icon
    {
        $icon = clone $this->icon;
        $icon->setSize(Icon::SIZE_LARGE);

        return $icon;
    }

    public function getUpdatedAt(): ?int
    {
        if ($this->resource instanceof File) {
            return $this->resource->getModificationTime();
        }
        if ($this->resource instanceof Folder) {
            return $this->resource->getModificationTime();
        }

        return null;
    }

    public function getCheckboxConfig(): ?array
    {
        if (($this->resource instanceof Folder || $this->resource instanceof File)
            && !$this->resource->checkActionPermission('read')) {
            return null;
        }

        return [
            'class' => 't3js-multi-record-selection-check',
            'name' => 'CBC[_FILE|' . md5($this->getIdentifier()) . ']',
            'value' => $this->getIdentifier(),
        ];
    }

    public function getContextMenuConfig(): array
    {
        return [
            'uid' => $this->getIdentifier(),
            'table' => 'sys_file',
        ];
    }

    /**
     * Calculates a state identifier used for drag&drop into the file tree
     */
    public function getStateIdentifier(): string
    {
        if ($this->resource instanceof Folder) {
            return $this->resource->getStorage()->getUid() . '_' . GeneralUtility::md5int($this->resource->getIdentifier());
        }

        return '';
    }
}
