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

use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Filelist\FileList;

/**
 * An event to modify the rendered row data for a file or folder in the File List.
 */
final class AfterFileListRowPreparedEvent
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private array $data,
        private readonly FileList $fileList,
        private array $attributes,
    ) {}

    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getFileList(): FileList
    {
        return $this->fileList;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }
}
