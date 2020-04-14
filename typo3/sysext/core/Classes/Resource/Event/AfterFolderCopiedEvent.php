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
use TYPO3\CMS\Core\Resource\FolderInterface;

/**
 * This event is fired after a folder was copied to the Resource Storage / Driver.
 *
 * Example: Custom listeners can analyze contents of a file or add custom permissions to a folder automatically.
 */
final class AfterFolderCopiedEvent
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
     * @var FolderInterface|null
     */
    private $targetFolder;

    public function __construct(Folder $folder, Folder $targetParentFolder, ?FolderInterface $targetFolder)
    {
        $this->folder = $folder;
        $this->targetParentFolder = $targetParentFolder;
        $this->targetFolder = $targetFolder;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    public function getTargetParentFolder(): Folder
    {
        return $this->targetParentFolder;
    }

    public function getTargetFolder(): ?FolderInterface
    {
        return $this->targetFolder;
    }
}
