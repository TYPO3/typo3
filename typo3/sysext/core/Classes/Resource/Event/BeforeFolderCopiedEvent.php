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
 * This event is fired before a folder is about to be copied to the Resource Storage / Driver.
 * Listeners could add deferred processing / queuing of large folders.
 */
final class BeforeFolderCopiedEvent
{
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @var Folder
     */
    private $targetParentFolder;

    /**
     * @var string
     */
    private $targetFolderName;

    public function __construct(Folder $folder, Folder $targetParentFolder, string $targetFolderName)
    {
        $this->folder = $folder;
        $this->targetParentFolder = $targetParentFolder;
        $this->targetFolderName = $targetFolderName;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    public function getTargetParentFolder(): Folder
    {
        return $this->targetParentFolder;
    }

    public function getTargetFolderName(): string
    {
        return $this->targetFolderName;
    }
}
