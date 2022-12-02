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

namespace TYPO3\CMS\Beuser\Domain\Model;

use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class FileMount extends AbstractEntity
{
    /**
     * Title of the file mount.
     *
     * @Extbase\Validate("NotEmpty")
     */
    protected string $title = '';

    /**
     * Description of the file mount.
     */
    protected string $description = '';

    /**
     * Identifier of the filemount
     */
    protected string $identifier = '';

    /**
     * Status of the filemount
     */
    protected bool $hidden = false;

    /**
     * Determines whether this file mount should be read only.
     */
    protected bool $readOnly = false;

    /**
     * Getter for the title of the file mount.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Setter for the title of the file mount.
     */
    public function setTitle(string $value): void
    {
        $this->title = $value;
    }

    /**
     * Getter for the description of the file mount.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Setter for the description of the file mount.
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Setter for the readOnly property of the file mount.
     */
    public function setReadOnly(bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    /**
     * Getter for the readOnly property of the file mount.
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * returns the path segment of the filemount (without the storage id)
     */
    public function getPath(): string
    {
        return explode(':/', $this->identifier)[1] ?? '';
    }

    /**
     * @todo This should be part of the ORM not the model class
     */
    public function getStorage(): ?ResourceStorage
    {
        return GeneralUtility::makeInstance(StorageRepository::class)->findByCombinedIdentifier($this->identifier);
    }
}
