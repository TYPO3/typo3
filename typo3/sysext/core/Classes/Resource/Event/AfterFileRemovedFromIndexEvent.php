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
 * This event is fired once a file was just removed in the database (sys_file).
 *
 * Example can be to further handle files and manage them separately outside of TYPO3's index.
 */
final class AfterFileRemovedFromIndexEvent
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
