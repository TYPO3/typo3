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
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Abstract base implementation of a processing task.
 */
abstract class AbstractTask implements TaskInterface
{
    protected Resource\File $sourceFile;
    protected bool $executed = false;
    protected bool $successful;

    public function __construct(
        protected ProcessedFile $targetFile,
        protected array $configuration
    ) {
        $this->sourceFile = $targetFile->getOriginalFile();
    }

    /**
     * Sets parameters needed in the checksum. Can be overridden to add additional parameters to the checksum.
     * This should include all parameters that could possibly vary between different task instances, e.g. the
     * TYPO3 image configuration in TYPO3_CONF_VARS[GFX] for graphic processing tasks.
     */
    protected function getChecksumData(): array
    {
        return [
            $this->getSourceFile()->getUid(),
            $this->getType() . '.' . $this->getName() . $this->getSourceFile()->getModificationTime(),
            (new ConfigurationService())->serialize($this->configuration),
        ];
    }

    /**
     * Returns the checksum for this task's configuration, also taking the file and task type into account.
     */
    public function getConfigurationChecksum(): string
    {
        return substr((string)md5(implode('|', $this->getChecksumData())), 0, 10);
    }

    /**
     * Returns the filename
     */
    public function getTargetFilename(): string
    {
        return $this->targetFile->getNameWithoutExtension()
            . '_' . $this->getConfigurationChecksum()
            . '.' . $this->getTargetFileExtension();
    }

    /**
     * Gets the file extension the processed file should
     * have in the filesystem.
     */
    public function getTargetFileExtension(): string
    {
        return $this->targetFile->getExtension();
    }

    /**
     * Returns the name of this task
     */
    abstract public function getName(): string;

    /**
     * Returns the type of this task
     */
    abstract public function getType(): string;

    public function getTargetFile(): Resource\ProcessedFile
    {
        return $this->targetFile;
    }

    public function getSourceFile(): Resource\File
    {
        return $this->sourceFile;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Returns TRUE if this task has been executed, no matter if the execution was successful.
     */
    public function isExecuted(): bool
    {
        return $this->executed;
    }

    /**
     * Set this task executed. This is used by the Processors in order to transfer the state of this task to
     * the file processing service.
     *
     * @param bool $successful Set this to FALSE if executing the task failed
     */
    public function setExecuted(bool $successful): void
    {
        $this->executed = true;
        $this->successful = $successful;
    }

    /**
     * Returns TRUE if this task has been successfully executed. Only call this method if the task has been processed
     * at all.
     *
     * @throws \LogicException If the task has not been executed already
     */
    public function isSuccessful(): bool
    {
        if (!$this->executed) {
            throw new \LogicException('Task has not been executed; cannot determine success.', 1352549235);
        }
        return $this->successful;
    }

    /**
     * We only have to trigger the file processing if the file either is new, does not exist or the
     * original file has changed since the last processing run (the last case has to trigger a reprocessing
     * even if the original file was used until now).
     */
    public function fileNeedsProcessing(): bool
    {
        $processedFile = $this->getTargetFile();
        if (!$processedFile->isProcessed()) {
            return true;
        }

        $checksum = $this->getTargetFile()->getProperty('checksum');
        $checksumCalculationOk = !$checksum || $this->getConfigurationChecksum() === $checksum;

        $fileNeedsReprocessing = $processedFile->isNew()
            || (!$processedFile->usesOriginalFile() && !$processedFile->exists())
            || ($processedFile->needsReprocessing() || !$checksumCalculationOk);

        if ($fileNeedsReprocessing && $this->getTargetFile()->exists()) {
            $this->getTargetFile()->delete();
        }

        return $fileNeedsReprocessing;
    }

    /**
     * Can be extended in the actual subclasses, but be careful on what to sanitize, as Processors might need
     * information that you actually throw away.
     *
     * Ensure that the processing configuration which is part of the hash sum is properly cast, so
     * unnecessary duplicate images are not produced, see #80942
     */
    public function sanitizeConfiguration(): void
    {
        foreach ($this->configuration as &$value) {
            if (MathUtility::canBeInterpretedAsInteger($value)) {
                $value = (int)$value;
            }
        }
        // @todo: ideally we would do a sort() on the array to really structure this, but then the checksums would change
        // @todo: and we would need to re-create all processed files again, but this would be something we should tackle at some point
    }
}
