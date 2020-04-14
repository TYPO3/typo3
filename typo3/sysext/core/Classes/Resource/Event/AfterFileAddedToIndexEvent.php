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
 * This event is fired once an index was just added to the database (= indexed).
 *
 * Examples: Allows to additionally populate custom fields of the sys_file/sys_file_metadata database records.
 */
final class AfterFileAddedToIndexEvent
{
    /**
     * @var int
     */
    private $fileUid;

    /**
     * @var array
     */
    private $record;

    public function __construct(int $fileUid, array $record)
    {
        $this->fileUid = $fileUid;
        $this->record = $record;
    }

    public function getFileUid(): int
    {
        return $this->fileUid;
    }

    public function getRecord(): array
    {
        return $this->record;
    }
}
