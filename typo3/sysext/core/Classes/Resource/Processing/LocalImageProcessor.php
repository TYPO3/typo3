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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Processes Local Images files
 */
class LocalImageProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Returns TRUE if this processor can process the given task.
     *
     * @param TaskInterface $task
     * @return bool
     */
    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true);
    }

    /**
     * Processes the given task.
     *
     * @param TaskInterface $task
     * @throws \InvalidArgumentException
     */
    public function processTask(TaskInterface $task): void
    {
        if ($this->checkForExistingTargetFile($task)) {
            return;
        }
        $this->processTaskWithLocalFile($task, null);
    }

    /**
     * Processes an image described in a task, but optionally uses a given local image
     *
     * @param TaskInterface $task
     * @param string|null $localFile
     * @throws \InvalidArgumentException
     */
    public function processTaskWithLocalFile(TaskInterface $task, ?string $localFile): void
    {
        $helper = $this->getHelperByTaskName($task->getName());
        try {
            if ($localFile === null) {
                $result = $helper->process($task);
            } else {
                $result = $helper->processWithLocalFile($task, $localFile);
            }
            if ($result === null) {
                $task->setExecuted(true);
                $task->getTargetFile()->setUsesOriginalFile();
            } elseif (!empty($result['filePath']) && file_exists($result['filePath'])) {
                $task->setExecuted(true);
                $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($result['filePath']);
                $task->getTargetFile()->setName($task->getTargetFileName());
                $task->getTargetFile()->updateProperties([
                    'width' => $imageDimensions[0] ?? 0,
                    'height' => $imageDimensions[1] ?? 0,
                    'size' => filesize($result['filePath']),
                    'checksum' => $task->getConfigurationChecksum(),
                ]);
                $task->getTargetFile()->updateWithLocalFile($result['filePath']);
            } else {
                // Seems we have no valid processing result
                $task->setExecuted(false);
            }
        } catch (\Exception $e) {
            // @todo: Swallowing all exceptions including PHP warnings here is a bad idea.
            // @todo: This should be restricted to more specific exceptions - if at all.
            // @todo: For now, we at least log the situation.
            $this->logger->error(sprintf('Processing task of image file'), ['exception' => $e]);
            $task->setExecuted(false);
        }
    }

    /**
     * Check if the to be processed target file already exists
     * if exist take info from that file and mark task as done
     *
     * @param TaskInterface $task
     * @return bool
     */
    protected function checkForExistingTargetFile(TaskInterface $task)
    {
        // the storage of the processed file, not of the original file!
        $storage = $task->getTargetFile()->getStorage();
        $processingFolder = $storage->getProcessingFolder($task->getSourceFile());

        // explicitly check for the raw filename here, as we check for files that existed before we even started
        // processing, i.e. that were processed earlier
        if ($processingFolder->hasFile($task->getTargetFileName())) {
            // When the processed file already exists set it as processed file
            $task->getTargetFile()->setName($task->getTargetFileName());

            // If the processed file is stored on a remote server, we must fetch a local copy of the file, as we
            // have no API for fetching file metadata from a remote file.
            $localProcessedFile = $storage->getFileForLocalProcessing($task->getTargetFile(), false);
            $task->setExecuted(true);
            $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($localProcessedFile);
            $properties = [
                'width' => $imageDimensions[0],
                'height' => $imageDimensions[1],
                'size' => filesize($localProcessedFile),
                'checksum' => $task->getConfigurationChecksum(),
            ];
            $task->getTargetFile()->updateProperties($properties);

            return true;
        }
        return false;
    }

    /**
     * @param string $taskName
     * @return LocalCropScaleMaskHelper|LocalPreviewHelper
     * @throws \InvalidArgumentException
     */
    protected function getHelperByTaskName($taskName)
    {
        switch ($taskName) {
            case 'Preview':
                $helper = GeneralUtility::makeInstance(LocalPreviewHelper::class);
            break;
            case 'CropScaleMask':
                $helper = GeneralUtility::makeInstance(LocalCropScaleMaskHelper::class);
            break;
            default:
                throw new \InvalidArgumentException('Cannot find helper for task name: "' . $taskName . '"', 1353401352);
        }

        return $helper;
    }

    /**
     * @return GraphicalFunctions
     */
    protected function getGraphicalFunctionsObject(): GraphicalFunctions
    {
        return GeneralUtility::makeInstance(GraphicalFunctions::class);
    }
}
