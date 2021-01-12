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
 * This event is fired after a folder was renamed.
 *
 * Examples: Add custom processing of folders or adjust permissions.
 */
final class AfterFolderRenamedEvent
{
    private Folder $folder;
    private Folder $sourceFolder;

    public function __construct(Folder $folder, Folder $sourceFolder)
    {
        $this->folder = $folder;
        $this->sourceFolder = $sourceFolder;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    public function getSourceFolder(): Folder
    {
        return $this->sourceFolder;
    }
}
