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
 * This event is fired once a file was just marked as missing in the database (sys_file).
 *
 * Example: If a file is marked as missing, listeners can try to recover a file. This can happen on specific setups
 * where editors also work via FTP.
 */
final class AfterFileMarkedAsMissingEvent
{
    /**
     * @var int
     */
    private $fileUid;

    public function __construct(int $fileUid)
    {
        $this->fileUid = $fileUid;
    }

    public function getFileUid(): int
    {
        return $this->fileUid;
    }
}
