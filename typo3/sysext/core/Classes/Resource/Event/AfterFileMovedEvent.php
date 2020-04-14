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

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;

/**
 * This event is fired after a file was moved within a Resource Storage / Driver.
 * The folder represents the "target folder".
 *
 * Examples: Use this to update custom third party handlers that rely on specific paths.
 */
final class AfterFileMovedEvent
{
    /**
     * @var FileInterface
     */
    private $file;

    /**
     * @var Folder
     */
    private $folder;

    /**
     * @var FolderInterface
     */
    private $originalFolder;

    public function __construct(FileInterface $file, Folder $folder, FolderInterface $originalFolder)
    {
        $this->file = $file;
        $this->folder = $folder;
        $this->originalFolder = $originalFolder;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    public function getOriginalFolder(): FolderInterface
    {
        return $this->originalFolder;
    }
}
