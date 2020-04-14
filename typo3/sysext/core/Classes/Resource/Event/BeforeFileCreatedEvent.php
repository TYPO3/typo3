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
 * This event is fired before a file is about to be created within a Resource Storage / Driver.
 * The folder represents the "target folder".
 *
 * This allows to further analyze or modify the file or filename before it is written by the driver.
 */
final class BeforeFileCreatedEvent
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var Folder
     */
    private $folder;

    public function __construct(string $fileName, Folder $folder)
    {
        $this->fileName = $fileName;
        $this->folder = $folder;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }
}
