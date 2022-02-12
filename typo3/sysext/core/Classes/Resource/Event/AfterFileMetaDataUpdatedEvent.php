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
 * This event is fired once metadata of a file was updated, in order to update custom metadata fields accordingly
 */
final class AfterFileMetaDataUpdatedEvent
{
    public function __construct(
        private readonly int $fileUid,
        private readonly int $metaDataUid,
        private readonly array $record
    ) {
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
}
