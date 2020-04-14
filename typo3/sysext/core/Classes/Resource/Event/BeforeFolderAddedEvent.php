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
 * This event is fired before a folder is about to be added to the Resource Storage / Driver.
 * This allows to further specify folder names according to regulations for a specific project.
 */
final class BeforeFolderAddedEvent
{
    /**
     * @var Folder
     */
    private $parentFolder;

    /**
     * @var string
     */
    private $folderName;

    public function __construct(Folder $parentFolder, string $folderName)
    {
        $this->parentFolder = $parentFolder;
        $this->folderName = $folderName;
    }

    public function getParentFolder(): Folder
    {
        return $this->parentFolder;
    }

    public function getFolderName(): string
    {
        return $this->folderName;
    }
}
