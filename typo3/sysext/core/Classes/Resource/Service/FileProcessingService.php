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

namespace TYPO3\CMS\Core\Resource\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * File processing service
 */
class FileProcessingService
{
    /**
     * @var Resource\ResourceStorage
     */
    protected $storage;

    /**
     * @var Resource\Driver\DriverInterface
     */
    protected $driver;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Creates this object.
     *
     * @param Resource\ResourceStorage $storage
     * @param Resource\Driver\DriverInterface $driver
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(ResourceStorage $storage, DriverInterface $driver, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->storage = $storage;
        $this->driver = $driver;
        $this->eventDispatcher = $eventDispatcher ?? GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    /**
     * Processes a file
     *
     * @param Resource\FileInterface $fileObject The file object
     * @param Resource\ResourceStorage $targetStorage The storage to store the processed file in
     * @param string $taskType
     * @param array $configuration
     *
     * @return Resource\ProcessedFile
     * @throws \InvalidArgumentException
     */
    public function processFile(FileInterface $fileObject, ResourceStorage $targetStorage, $taskType, $configuration)
    {
        // Enforce default configuration for preview processing here,
        // to be sure we find already processed files below,
        // which we wouldn't if we would change the configuration later, as configuration is part of the lookup.
        if ($taskType === ProcessedFile::CONTEXT_IMAGEPREVIEW) {
            $configuration = LocalPreviewHelper::preProcessConfiguration($configuration);
        }
        // Ensure that the processing configuration which is part of the hash sum is properly cast, so
        // unnecessary duplicate images are not produced, see #80942
        foreach ($configuration as &$value) {
            if (MathUtility::canBeInterpretedAsInteger($value)) {
                $value = (int)$value;
            }
        }

        $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $processedFile = $processedFileRepository->findOneByOriginalFileAndTaskTypeAndConfiguration($fileObject, $taskType, $configuration);

        // set the storage of the processed file
        // Pre-process the file
        /** @var Resource\Event\BeforeFileProcessingEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new BeforeFileProcessingEvent($this->driver, $processedFile, $fileObject, $taskType, $configuration)
        );
        $processedFile = $event->getProcessedFile();

        // Only handle the file if it is not processed yet
        // (maybe modified or already processed by a signal)
        // or (in case of preview images) already in the DB/in the processing folder
        if (!$processedFile->isProcessed()) {
            $this->process($processedFile, $targetStorage);
        }

        // Post-process (enrich) the file
        /** @var Resource\Event\AfterFileProcessingEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new AfterFileProcessingEvent($this->driver, $processedFile, $fileObject, $taskType, $configuration)
        );

        return $event->getProcessedFile();
    }

    /**
     * Processes the file
     *
     * @param Resource\ProcessedFile $processedFile
     * @param Resource\ResourceStorage $targetStorage The storage to put the processed file into
     */
    protected function process(ProcessedFile $processedFile, ResourceStorage $targetStorage)
    {
        // We only have to trigger the file processing if the file either is new, does not exist or the
        // original file has changed since the last processing run (the last case has to trigger a reprocessing
        // even if the original file was used until now)
        if ($processedFile->isNew() || (!$processedFile->usesOriginalFile() && !$processedFile->exists()) ||
            $processedFile->isOutdated()) {
            $task = $processedFile->getTask();
            $processor = $this->getProcessorByTask($task);
            $processor->processTask($task);

            if ($task->isExecuted() && $task->isSuccessful() && $processedFile->isProcessed()) {
                $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
                $processedFileRepository->add($processedFile);
            }
        }
    }

    /**
     * @param Resource\Processing\TaskInterface $task
     * @return Resource\Processing\ProcessorInterface
     */
    protected function getProcessorByTask(TaskInterface $task): ProcessorInterface
    {
        $processorRegistry = GeneralUtility::makeInstance(ProcessorRegistry::class);

        return $processorRegistry->getProcessorByTask($task);
    }
}
