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

namespace TYPO3\CMS\Core\Resource\OnlineMedia\Processing;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Event\AfterVideoPreviewFetchedEvent;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

/**
 * Preview of Online Media item Processing
 */
final class PreviewProcessing implements ProcessorInterface
{
    public function __construct(
        protected readonly OnlineMediaHelperRegistry $onlineMediaHelperRegistry,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly LocalImageProcessor $localImageProcessor,
    ) {}

    public function canProcessTask(TaskInterface $task): bool
    {
        if ($task->getType() !== 'Image') {
            return false;
        }
        if (!in_array($task->getName(), ['Preview', 'CropScaleMask'], true)) {
            return false;
        }
        $sourceFile = $task->getSourceFile();
        if (!$this->onlineMediaHelperRegistry->hasOnlineMediaHelper($sourceFile->getExtension())) {
            return false;
        }
        $previewImageFile = $this->getPreviewImageFromOnlineMedia($sourceFile);
        return !empty($previewImageFile) && file_exists($previewImageFile);
    }

    public function processTask(TaskInterface $task): void
    {
        $this->localImageProcessor->processTaskWithLocalFile(
            $task,
            $this->getPreviewImageFromOnlineMedia($task->getSourceFile())
        );
    }

    protected function getPreviewImageFromOnlineMedia(File $file): string
    {
        $onlineMediaHelper = $this->onlineMediaHelperRegistry->getOnlineMediaHelper($file);
        $previewImage = $onlineMediaHelper->getPreviewImage($file);

        $videoPreviewEvent = new AfterVideoPreviewFetchedEvent($file, $onlineMediaHelper, $previewImage);
        $this->eventDispatcher->dispatch($videoPreviewEvent);

        return $videoPreviewEvent->getPreviewImageFilename();
    }
}
