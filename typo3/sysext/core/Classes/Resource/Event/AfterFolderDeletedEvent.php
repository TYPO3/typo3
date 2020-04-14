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

use TYPO3\CMS\Core\Resource\Folder;

/**
 * This event is fired after a folder was deleted. Custom listeners can then further clean up permissions or
 * third-party processed files with this event.
 */
final class AfterFolderDeletedEvent
{
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @var bool
     */
    private $wasDeleted;

    public function __construct(Folder $folder, bool $wasDeleted)
    {
        $this->folder = $folder;
        $this->wasDeleted = $wasDeleted;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    public function isDeleted(): bool
    {
        return $this->wasDeleted;
    }
}
