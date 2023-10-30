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

namespace TYPO3\CMS\Core\Resource\Processing;

use TYPO3\CMS\Core\Resource;

/**
 * A task is a unit of work that can be performed by a file processor. This may include multiple steps in any order,
 * details depend on the configuration of the task and the tools the processor uses.
 *
 * Each task has a type and a name. The type describes the category of the task, like "image" and "video". If your task
 * is generic or applies to multiple types of files, use "general".
 *
 * A task also already has to know the target file it should be executed on, so there is no "abstract" task that just
 * specifies the steps to be executed without a concrete file. However, new tasks can easily be created from an
 * existing task object.
 */
interface TaskInterface
{
    /**
     * Returns the name of this task.
     */
    public function getName(): string;

    /**
     * Returns the type of this task.
     */
    public function getType(): string;

    /**
     * Returns the processed file this task is executed on.
     */
    public function getTargetFile(): Resource\ProcessedFile;

    /**
     * Returns the original file this task is based on.
     */
    public function getSourceFile(): Resource\File;

    /**
     * Returns the configuration for this task.
     */
    public function getConfiguration(): array;

    /**
     * Returns the configuration checksum of this task.
     */
    public function getConfigurationChecksum(): string;

    /**
     * Returns the name the processed file should have in the filesystem.
     */
    public function getTargetFileName(): string;

    /**
     * Gets the file extension the processed file should have in the filesystem.
     */
    public function getTargetFileExtension(): string;

    /**
     * Returns TRUE if the file has to be processed at all, such as e.g. the original file does.
     *
     * Note: This does not indicate if the concrete ProcessedFile attached to this task has to be (re)processed.
     * This check is done in ProcessedFile::isOutdated(). @todo isOutdated()/needsReprocessing()?
     */
    public function fileNeedsProcessing(): bool;

    /**
     * Returns TRUE if this task has been executed, no matter if the execution was successful.
     */
    public function isExecuted(): bool;

    /**
     * Mark this task as executed. This is used by the Processors in order to transfer the state of this task to
     * the file processing service.
     *
     * @param bool $successful Set this to FALSE if executing the task failed
     */
    public function setExecuted(bool $successful): void;

    /**
     * Returns TRUE if this task has been successfully executed. Only call this method if the task has been processed
     * at all.
     *
     * @throws \LogicException If the task has not been executed already
     */
    public function isSuccessful(): bool;

    /**
     * For some tasks it might be important and useful to clean up the configuration, in order to find the
     * ProcessedFile that uses this configuration.
     *
     * Ideally, a task has some information what needs to be used or not.
     */
    public function sanitizeConfiguration(): void;
}
