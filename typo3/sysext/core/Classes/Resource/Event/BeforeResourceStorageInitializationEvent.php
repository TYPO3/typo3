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

namespace TYPO3\CMS\Core\Resource\Event;

/**
 * This event is fired before a resource object is actually built/created.
 *
 * Example: A database record can be enriched to add dynamic values to each resource (file/folder) before
 * creation of a storage
 */
final class BeforeResourceStorageInitializationEvent
{
    /**
     * @var int
     */
    private $storageUid;

    /**
     * @var array
     */
    private $record;

    /**
     * @var string|null
     */
    private $fileIdentifier;

    public function __construct(int $storageUid, array $record, ?string $fileIdentifier)
    {
        $this->storageUid = $storageUid;
        $this->record = $record;
        $this->fileIdentifier = $fileIdentifier;
    }

    public function getStorageUid(): int
    {
        return $this->storageUid;
    }

    public function setStorageUid(int $storageUid): void
    {
        $this->storageUid = $storageUid;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getFileIdentifier(): ?string
    {
        return $this->fileIdentifier;
    }

    public function setFileIdentifier(?string $fileIdentifier): void
    {
        $this->fileIdentifier = $fileIdentifier;
    }
}
