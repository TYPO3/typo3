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
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * This event is fired after a file object has been processed.
 *
 * This allows to further customize a file object's processed file.
 *
 * The driver is only present for a FAL File; a resource outside the File Abstraction
 * Layer (for example a system resource shipped with a package) has none, since it is
 * never fetched through a ResourceStorage driver in the first place.
 */
final class AfterFileProcessingEvent
{
    public function __construct(
        private readonly ?DriverInterface $driver,
        private ProcessedResourceInterface $processedFile,
        private readonly FileInterface|SystemResourceInterface $file,
        private readonly string $taskType,
        private readonly array $configuration
    ) {}

    public function getProcessedFile(): ProcessedResourceInterface
    {
        return $this->processedFile;
    }

    public function setProcessedFile(ProcessedResourceInterface $processedFile): void
    {
        $this->processedFile = $processedFile;
    }

    public function getDriver(): ?DriverInterface
    {
        return $this->driver;
    }

    public function getFile(): FileInterface|SystemResourceInterface
    {
        return $this->file;
    }

    public function getTaskType(): string
    {
        return $this->taskType;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
