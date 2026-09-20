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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * A resource that is the result of processing (resizing, cropping, converting, ...) another,
 * original resource - implemented by both the FAL-backed ProcessedFile and, for resources
 * outside the File Abstraction Layer, ProcessedResource.
 *
 * This interface is public API and can be referenced in third party code, for example
 * by a ResourceAwareProcessorInterface implementation working on a task's target file.
 * Implementations of this interface are internal though.
 */
interface ProcessedResourceInterface extends SystemResourceInterface
{
    public function isNew(): bool;

    public function isProcessed(): bool;

    public function usesOriginalFile(): bool;

    public function needsReprocessing(): bool;

    public function exists(): bool;

    public function delete(): bool;

    public function getProperty(string $key): mixed;

    /**
     * Returns the size of the processed resource in bytes.
     */
    public function getSize(): int;

    public function setName(string $name): void;

    public function updateProperties(array $properties): void;

    /**
     * Injects a local file, which is a processing result, into the object.
     */
    public function updateWithLocalFile(string $localFilePath): void;

    /**
     * Defines that the original resource should be used as-is, since no processing was needed.
     */
    public function setUsesOriginalFile(): void;

    public function getOriginalResource(): SystemResourceInterface;

    public function getPublicUrl(): ?string;

    /**
     * Returns the processing configuration this resource was created for.
     */
    public function getProcessingConfiguration(): array;

    /**
     * Returns the task type this resource was created for.
     */
    public function getTaskIdentifier(): string;
}
