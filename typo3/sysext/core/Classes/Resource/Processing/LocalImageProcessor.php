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
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Processes Local Images files
 */
class LocalImageProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Returns TRUE if this processor can process the given task.
     */
    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true);
    }

    /**
     * Processes the given task.
     *
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
                $imageInformation = GeneralUtility::makeInstance(ImageInfo::class, $result['filePath']);
                if (($result['remapProcessedTargetFileExtension'] ?? null) !== null) {
                    // Processing changed the target filename extension to something else.
                    // We need to react on this, because otherwise the file contents will not
                    // match the file extension.
                    $task->getTargetFile()->setName($task->getTargetFileName() . '.' . $result['remapProcessedTargetFileExtension']);
                } else {
                    $task->getTargetFile()->setName($task->getTargetFileName());
                }
                $task->getTargetFile()->updateProperties([
                    'width' => $imageInformation->getWidth(),
                    'height' => $imageInformation->getHeight(),
                    'size' => $imageInformation->getSize(),
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
     * Check if the target file that is to be processed already exists.
     * If it exists, use the metadata from that file and mark task as done.
     */
    protected function checkForExistingTargetFile(TaskInterface $task): bool
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
            $imageInformation = GeneralUtility::makeInstance(ImageInfo::class, $localProcessedFile);
            $properties = [
                'width' => $imageInformation->getWidth(),
                'height' => $imageInformation->getHeight(),
                'size' => $imageInformation->getSize(),
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
}
