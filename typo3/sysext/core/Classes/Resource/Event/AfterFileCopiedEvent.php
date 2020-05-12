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

/**
 * This event is fired after a file was copied within a Resource Storage / Driver.
 * The folder represents the "target folder".
 *
 * Example: Listeners can sign up for listing duplicates using this event.
 */
final class AfterFileCopiedEvent
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
     * @var string
     */
    private $newFileIdentifier;

    /**
     * @var FileInterface|null
     */
    private $newFile;

    public function __construct(FileInterface $file, Folder $folder, string $newFileIdentifier, ?FileInterface $newFile)
    {
        $this->file = $file;
        $this->folder = $folder;
        $this->newFileIdentifier = $newFileIdentifier;
        $this->newFile = $newFile;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    public function getNewFileIdentifier(): string
    {
        return $this->newFileIdentifier;
    }

    public function getNewFile(): ?FileInterface
    {
        return $this->newFile;
    }
}
