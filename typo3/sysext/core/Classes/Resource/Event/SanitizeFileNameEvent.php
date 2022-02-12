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

use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * This event is fired once an index was just added to the database (= indexed), so it is possible
 * to modify the file name, and name the files according to naming conventions of a specific project.
 */
final class SanitizeFileNameEvent
{
    public function __construct(
        private string $fileName,
        private readonly Folder $targetFolder,
        private readonly ResourceStorage $storage,
        private readonly DriverInterface $driver
    ) {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getTargetFolder(): Folder
    {
        return $this->targetFolder;
    }

    public function getStorage(): ResourceStorage
    {
        return $this->storage;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }
}
