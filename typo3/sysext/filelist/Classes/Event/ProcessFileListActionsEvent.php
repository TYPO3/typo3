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

namespace TYPO3\CMS\Filelist\Event;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Event fired to modify icons rendered for the file listings
 */
final class ProcessFileListActionsEvent
{
    public function __construct(private readonly ResourceInterface $fileOrFolder, private array $actionItems) {}

    public function getResource(): ResourceInterface
    {
        return $this->fileOrFolder;
    }

    public function isFile(): bool
    {
        return $this->fileOrFolder instanceof FileInterface;
    }

    public function getActionItems(): array
    {
        return $this->actionItems;
    }

    public function setActionItems(array $actionItems): void
    {
        $this->actionItems = $actionItems;
    }
}
