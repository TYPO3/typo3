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

use TYPO3\CMS\Core\Resource\FolderInterface;

/**
 * Event that is fired after the default upload folder for a user was checked
 */
final class AfterDefaultUploadFolderWasResolvedEvent
{
    public function __construct(
        private ?FolderInterface $uploadFolder,
        private readonly ?int $pid,
        private readonly ?string $table,
        private readonly ?string $fieldName
    ) {}

    public function getUploadFolder(): ?FolderInterface
    {
        return $this->uploadFolder;
    }

    public function setUploadFolder(FolderInterface $uploadFolder): void
    {
        $this->uploadFolder = $uploadFolder;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getFieldName(): string | null
    {
        return $this->fieldName;
    }
}
