<?php

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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;

/**
 * Abstract base implementation of a task.
 *
 * If you extend this class, make sure that you redefine the member variables $type and $name
 * or set them in the constructor. Otherwise your task won't be recognized by the system and several
 * things will fail.
 */
abstract class AbstractTask implements TaskInterface
{
    /**
     * @var array
     */
    protected $checksumData = [];

    /**
     * @var Resource\ProcessedFile
     */
    protected $targetFile;

    /**
     * @var Resource\File
     */
    protected $sourceFile;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $executed = false;

    /**
     * @var bool
     */
    protected $successful;

    /**
     * @param Resource\ProcessedFile $targetFile
     * @param array $configuration
     */
    public function __construct(ProcessedFile $targetFile, array $configuration)
    {
        $this->targetFile = $targetFile;
        $this->sourceFile = $targetFile->getOriginalFile();
        $this->configuration = $configuration;
    }

    /**
     * Sets parameters needed in the checksum. Can be overridden to add additional parameters to the checksum.
     * This should include all parameters that could possibly vary between different task instances, e.g. the
     * TYPO3 image configuration in TYPO3_CONF_VARS[GFX] for graphic processing tasks.
     *
     * @return array
     */
    protected function getChecksumData()
    {
        return [
            $this->getSourceFile()->getUid(),
            $this->getType() . '.' . $this->getName() . $this->getSourceFile()->getModificationTime(),
            serialize($this->configuration),
        ];
    }

    /**
     * Returns the checksum for this task's configuration, also taking the file and task type into account.
     *
     * @return string
     */
    public function getConfigurationChecksum()
    {
        return substr((string)md5(implode('|', $this->getChecksumData())), 0, 10);
    }

    /**
     * Returns the filename
     *
     * @return string
     */
    public function getTargetFilename()
    {
        return $this->targetFile->getNameWithoutExtension()
            . '_' . $this->getConfigurationChecksum()
            . '.' . $this->getTargetFileExtension();
    }

    /**
     * Gets the file extension the processed file should
     * have in the filesystem.
     *
     * @return string
     */
    public function getTargetFileExtension()
    {
        return $this->targetFile->getExtension();
    }

    /**
     * Returns the name of this task
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the type of this task
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Resource\ProcessedFile
     */
    public function getTargetFile()
    {
        return $this->targetFile;
    }

    /**
     * @param Resource\ProcessedFile $targetFile
     */
    public function setTargetFile(ProcessedFile $targetFile)
    {
        $this->targetFile = $targetFile;
    }

    /**
     * @return Resource\File
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * @param Resource\File $sourceFile
     */
    public function setSourceFile(File $sourceFile)
    {
        $this->sourceFile = $sourceFile;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Checks if the given configuration is sensible for this task, i.e. if all required parameters
     * are given, within the boundaries and don't conflict with each other.
     *
     * @param array $configuration
     * @return bool
     */
    abstract protected function isValidConfiguration(array $configuration);

    /**
     * Returns TRUE if this task has been executed, no matter if the execution was successful.
     *
     * @return bool
     */
    public function isExecuted()
    {
        return $this->executed;
    }

    /**
     * Set this task executed. This is used by the Processors in order to transfer the state of this task to
     * the file processing service.
     *
     * @param bool $successful Set this to FALSE if executing the task failed
     */
    public function setExecuted($successful)
    {
        $this->executed = true;
        $this->successful = $successful;
    }

    /**
     * Returns TRUE if this task has been successfully executed. Only call this method if the task has been processed
     * at all.
     * @return bool
     * @throws \LogicException If the task has not been executed already
     */
    public function isSuccessful()
    {
        if (!$this->executed) {
            throw new \LogicException('Task has not been executed; cannot determine success.', 1352549235);
        }

        return $this->successful;
    }
}
