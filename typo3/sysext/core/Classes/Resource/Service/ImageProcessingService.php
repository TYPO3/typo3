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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\FileProcessingAspect;
use TYPO3\CMS\Core\Locking\ResourceMutex;
use TYPO3\CMS\Core\Resource\Exception\FileAlreadyProcessedException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;

/**
 * Disables deferred processing and actually processes a preprocessed processed file
 */
class ImageProcessingService
{
    /**
     * @var ProcessedFileRepository
     */
    private $processedFileRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var ResourceMutex
     */
    private $locker;

    public function __construct(
        ProcessedFileRepository $processedFileRepository,
        Context $context,
        ResourceMutex $locker
    ) {
        $this->processedFileRepository = $processedFileRepository;
        $this->context = $context;
        $this->locker = $locker;
    }

    public function process(int $processedFileId): ProcessedFile
    {
        /** @var ProcessedFile $processedFile */
        $processedFile = $this->processedFileRepository->findByUid($processedFileId);
        try {
            $this->validateProcessedFile($processedFile);
            $this->locker->acquireLock(self::class, $processedFileId);

            // Fetch the processed file again, as it might have been processed by another process while waiting for the lock
            /** @var ProcessedFile $processedFile */
            $processedFile = $this->processedFileRepository->findByUid($processedFileId);
            $this->validateProcessedFile($processedFile);

            $this->context->setAspect('fileProcessing', new FileProcessingAspect(false));
            $processedFile = $processedFile->getOriginalFile()->process(
                $processedFile->getTaskIdentifier(),
                $processedFile->getProcessingConfiguration()
            );

            $this->validateProcessedFile($processedFile);
        } catch (FileAlreadyProcessedException $e) {
            $processedFile = $e->getProcessedFile();
        } finally {
            $this->locker->releaseLock(self::class);
        }

        return $processedFile;
    }

    /**
     * Check whether a processed file was already processed
     *
     * @param ProcessedFile $processedFile
     * @throws FileAlreadyProcessedException
     */
    private function validateProcessedFile(ProcessedFile $processedFile): void
    {
        if ($processedFile->isProcessed()) {
            throw new FileAlreadyProcessedException($processedFile, 1599395651);
        }
    }
}
