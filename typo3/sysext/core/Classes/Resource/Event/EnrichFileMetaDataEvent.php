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
 * Event that is called after a record has been loaded from database
 * Allows other places to do extension of metadata at runtime or
 * for example translation and workspace overlay.
 */
final class EnrichFileMetaDataEvent
{
    /**
     * @var int
     */
    private $fileUid;

    /**
     * @var int
     */
    private $metaDataUid;

    /**
     * @var array
     */
    private $record;

    public function __construct(int $fileUid, int $metaDataUid, array $record)
    {
        $this->fileUid = $fileUid;
        $this->metaDataUid = $metaDataUid;
        $this->record = $record;
    }

    public function getFileUid(): int
    {
        return $this->fileUid;
    }

    public function getMetaDataUid(): int
    {
        return $this->metaDataUid;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }
}
