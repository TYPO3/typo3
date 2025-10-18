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

namespace TYPO3\CMS\Core\Resource\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;

/**
 * This is a general service for creating Processed Files a.k.a. processing a File object with a given configuration.
 *
 * This is how it works:
 *   -> File->process(string $taskType, array $configuration)
 *      -> ResourceStorage->processFile(File $file, $taskType, array $configuration)
 *         -> FileProcessingService->processFile(File $file, $taskType, array $configuration)
 *
 * This class then transforms the information of a Task through a Processor into a ProcessedFile object.
 * For this, the DB is checked if there is a ProcessedFile which has been processed or does not need
 * to be processed. If processing is required, a valid Processor is searched for to process the
 * Task object (which is created from the TaskTypeRegistry when needed for processing).
 */
#[Autoconfigure(public: true)]
readonly class FileProcessingService
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ProcessedFileRepository $processedFileRepository,
        protected ProcessorRegistry $processorRegistry,
        protected TaskTypeRegistry $taskTypeRegistry,
    ) {}

    public function processFile(File|FileReference $fileObject, string $taskType, DriverInterface $driver, array $configuration): ProcessedFile
    {
        // Processing always works on the original file
        $originalFile = $fileObject instanceof FileReference ? $fileObject->getOriginalFile() : $fileObject;

        // Find an entry in the DB or create a new ProcessedFile which can then be added (see ->add below)
        $processedFile = $this->processedFileRepository->findOneByOriginalFileAndTaskTypeAndConfiguration($originalFile, $taskType, $configuration);

        // Make sure to work with the sanitized configuration from now on!
        $configuration = $processedFile->getProcessingConfiguration();

        // Pre-process the file
        $event = $this->eventDispatcher->dispatch(
            new BeforeFileProcessingEvent($driver, $processedFile, $fileObject, $taskType, $configuration)
        );
        $processedFile = $event->getProcessedFile();
        $task = $this->taskTypeRegistry->getTaskForType($taskType, $processedFile, $configuration);

        // Only handle the file if it is not processed yet
        // (maybe modified or already processed by an event)
        // or (in case of preview images) already in the DB/in the processing folder
        if ($task->fileNeedsProcessing()) {
            $this->getProcessorByTask($task)->processTask($task);
            if ($task->isExecuted() && $task->isSuccessful() && $processedFile->isProcessed()) {
                $this->processedFileRepository->add($processedFile, $task);
            }
        }

        // Post-process (enrich) the file
        $event = $this->eventDispatcher->dispatch(
            new AfterFileProcessingEvent($driver, $processedFile, $fileObject, $taskType, $configuration)
        );

        return $event->getProcessedFile();
    }

    protected function getProcessorByTask(TaskInterface $task): ProcessorInterface
    {
        return $this->processorRegistry->getProcessorByTask($task);
    }
}
