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
use TYPO3\CMS\Core\Resource\ProcessedResource;
use TYPO3\CMS\Core\Resource\ProcessedResourceCache;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

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
 *
 * A resource outside the File Abstraction Layer (for example a system resource shipped
 * with a package) takes the same path through this method, using ProcessedResourceCache
 * instead of ProcessedFileRepository, and TaskTypeRegistry to build its task the same
 * way the FAL branch does - the only places the underlying storage genuinely differs.
 * Everything after that - event dispatch, processor selection, processTask(),
 * fileNeedsProcessing() - is the same code for both, not a duplicate of it.
 */
#[Autoconfigure(public: true)]
readonly class FileProcessingService
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ProcessedFileRepository $processedFileRepository,
        protected ProcessorRegistry $processorRegistry,
        protected TaskTypeRegistry $taskTypeRegistry,
        protected ProcessedResourceCache $processedResourceCache,
    ) {}

    /**
     * @todo Currently, the underlying code is not flexible enough to introduce ProcessableFileInterface here. Ideally
     *       this should be adjusted in the future.
     */
    public function processFile(File|FileReference|SystemResourceInterface $fileObject, string $taskType, ?DriverInterface $driver, array $configuration): ProcessedFile|ProcessedResource
    {
        // Processing always works on the original resource
        $originalResource = $fileObject instanceof FileReference ? $fileObject->getOriginalFile() : $fileObject;

        if ($originalResource instanceof File) {
            // Find an entry in the DB or create a new ProcessedFile which can then be added (see ->add below)
            $processedFile = $this->processedFileRepository->findOneByOriginalFileAndTaskTypeAndConfiguration($originalResource, $taskType, $configuration);
            // Make sure to work with the sanitized configuration from now on!
            $configuration = $processedFile->getProcessingConfiguration();
        } else {
            // Sanitize before it becomes part of the cache lookup key below, the same
            // way the FAL branch above already does via the repository call - otherwise
            // two requests for what is really the same configuration, differing only in
            // an un-normalized value (e.g. "200" vs 200), would hash to distinct entries.
            $sanitizingTask = $this->taskTypeRegistry->getTaskForType($taskType, new ProcessedResource($this->processedResourceCache, $originalResource, $taskType, $configuration), $configuration);
            $sanitizingTask->sanitizeConfiguration();
            $configuration = $sanitizingTask->getConfiguration();
            $processedFile = $this->processedResourceCache->findOneByOriginalAndTaskTypeAndConfiguration($originalResource, $taskType, $configuration);
        }

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
                if ($processedFile instanceof ProcessedFile) {
                    $this->processedFileRepository->add($processedFile, $task);
                } elseif ($processedFile instanceof ProcessedResource) {
                    $this->processedResourceCache->add($processedFile);
                }
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
