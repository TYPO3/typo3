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
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
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

    public bool $isDownloadable = true;
    public bool $isSelectable = true;
    public bool $isSelected = false;

    public function __construct(
        public readonly ResourceInterface $resource,
        public readonly UserPermissions $userPermissions,
        public readonly Icon $icon
    ) {}

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

    /**
     * Calculates a state identifier used for drag&drop into the file tree
     */
    public function getStateIdentifier(): string
    {
        return $this->resource->getStorage()->getUid() . '_' . GeneralUtility::md5int($this->resource->getIdentifier());
    }

    public function getMetaDataUid(): ?int
    {
        if ($this->resource instanceof File
            && $this->canEditMetadata()) {
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

    public function getPath(): string
    {
        $resource = $this->resource;
        if ($resource instanceof File && !$resource->isMissing()) {
            $resource = $resource->getParentFolder();
        }
        if ($resource instanceof Folder) {
            return $resource->getReadablePath();
        }

        return '';
    }

    public function getPublicUrl(): ?string
    {
        if (!$this->resource instanceof File) {
            return null;
        }

        return $this->resource->getPublicUrl();
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

    public function getThumbnailUri(): ?string
    {
        $preview = $this->getPreview();
        if (!$preview) {
            return null;
        }

        return $preview
            ->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, ['width' => '32c', 'height' => '32c'])
            ->getPublicUrl() ?? null;
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

    public function getCreatedAt(): ?int
    {
        if ($this->resource instanceof File) {
            return $this->resource->getCreationTime();
        }
        if ($this->resource instanceof Folder) {
            return $this->resource->getCreationTime();
        }

        return null;
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
            'checked' => $this->isSelected,
        ];
    }

    public function isMissing(): ?bool
    {
        if ($this->resource instanceof File) {
            return $this->resource->isMissing();
        }

        return null;
    }

    public function isLocked(): bool
    {
        if ($this->resource instanceof InaccessibleFolder) {
            return true;
        }

        return false;
    }

    public function canEditMetadata(): bool
    {
        return $this->resource instanceof File
            && $this->resource->isIndexed()
            && $this->resource->checkActionPermission('editMeta')
            && $this->userPermissions->editMetaData;
    }

    public function canRead(): ?bool
    {
        if ($this->resource instanceof File || $this->resource instanceof Folder) {
            return $this->resource->checkActionPermission('read');
        }

        return null;
    }

    public function canWrite(): ?bool
    {
        if ($this->resource instanceof File || $this->resource instanceof Folder) {
            return $this->resource->checkActionPermission('write');
        }

        return null;
    }

    public function canDelete(): ?bool
    {
        if ($this->resource instanceof File || $this->resource instanceof Folder) {
            return $this->resource->checkActionPermission('delete');
        }

        return null;
    }

    public function canCopy(): ?bool
    {
        if ($this->resource instanceof File || $this->resource instanceof Folder) {
            return $this->resource->checkActionPermission('copy');
        }

        return null;
    }

    public function canRename(): ?bool
    {
        if ($this->resource instanceof File || $this->resource instanceof Folder) {
            return $this->resource->checkActionPermission('rename');
        }

        return null;
    }

    public function canMove(): ?bool
    {
        if ($this->resource instanceof File || $this->resource instanceof Folder) {
            return $this->resource->checkActionPermission('move');
        }

        return null;
    }
}
