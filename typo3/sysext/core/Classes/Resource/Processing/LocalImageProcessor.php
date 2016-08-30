<?php
namespace TYPO3\CMS\Core\Resource\Processing;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Processes Local Images files
 */
class LocalImageProcessor implements ProcessorInterface
{
    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
        $logManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class);
        $this->logger = $logManager->getLogger(__CLASS__);
    }

    /**
     * Returns TRUE if this processor can process the given task.
     *
     * @param TaskInterface $task
     * @return bool
     */
    public function canProcessTask(TaskInterface $task)
    {
        $canProcessTask = $task->getType() === 'Image';
        $canProcessTask = $canProcessTask & in_array($task->getName(), ['Preview', 'CropScaleMask']);
        return $canProcessTask;
    }

    /**
     * Processes the given task.
     *
     * @param TaskInterface $task
     * @throws \InvalidArgumentException
     */
    public function processTask(TaskInterface $task)
    {
        if (!$this->canProcessTask($task)) {
            throw new \InvalidArgumentException('Cannot process task of type "' . $task->getType() . '.' . $task->getName() . '"', 1350570621);
        }
        if ($this->checkForExistingTargetFile($task)) {
            return;
        }
        $helper = $this->getHelperByTaskName($task->getName());
        try {
            $result = $helper->process($task);
            if ($result === null) {
                $task->setExecuted(true);
                $task->getTargetFile()->setUsesOriginalFile();
            } elseif (!empty($result['filePath']) && file_exists($result['filePath'])) {
                $task->setExecuted(true);
                $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($result['filePath']);
                $task->getTargetFile()->setName($task->getTargetFileName());
                $task->getTargetFile()->updateProperties(
                    ['width' => $imageDimensions[0], 'height' => $imageDimensions[1], 'size' => filesize($result['filePath']), 'checksum' => $task->getConfigurationChecksum()]
                );
                $task->getTargetFile()->updateWithLocalFile($result['filePath']);

            // New dimensions + no new file (for instance svg)
            } elseif (!empty($result['width']) && !empty($result['height']) && empty($result['filePath'])) {
                $task->setExecuted(true);
                $task->getTargetFile()->setUsesOriginalFile();
                $task->getTargetFile()->updateProperties(
                    ['width' => $result['width'], 'height' => $result['height'], 'size' => $task->getSourceFile()->getSize(), 'checksum' => $task->getConfigurationChecksum()]
                );

            // Seems we have no valid processing result
            } else {
                $task->setExecuted(false);
            }
        } catch (\Exception $e) {
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
                'checksum' => $task->getConfigurationChecksum()
            ];
            $task->getTargetFile()->updateProperties($properties);

            return true;
        } else {
            return false;
        }
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
                $helper = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper::class, $this);
            break;
            case 'CropScaleMask':
                $helper = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper::class, $this);
            break;
            default:
                throw new \InvalidArgumentException('Cannot find helper for task name: "' . $taskName . '"', 1353401352);
        }

        return $helper;
    }

    /**
     * Creates error image based on gfx/notfound_thumb.png
     * Requires GD lib enabled, otherwise it will exit with the three
     * textstrings outputted as text. Outputs the image stream to browser and exits!
     *
     * @param string $filename Name of the file
     * @param string $textline1 Text line 1
     * @param string $textline2 Text line 2
     * @param string $textline3 Text line 3
     * @return void
     * @throws \RuntimeException
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use \TYPO3\CMS\Core\Imaging\GraphicalFunctions::getTemporaryImageWithText() instead.
     */
    public function getTemporaryImageWithText($filename, $textline1, $textline2, $textline3)
    {
        GeneralUtility::logDeprecatedFunction();
        $graphicalFunctions = $this->getGraphicalFunctionsObject();
        $graphicalFunctions->getTemporaryImageWithText($filename, $textline1, $textline2, $textline3);
    }

    /**
     * @return \TYPO3\CMS\Core\Imaging\GraphicalFunctions
     */
    protected function getGraphicalFunctionsObject()
    {
        static $graphicalFunctionsObject = null;

        if ($graphicalFunctionsObject === null) {
            $graphicalFunctionsObject = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\GraphicalFunctions::class);
        }

        return $graphicalFunctionsObject;
    }
}
