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

use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Preview of Online Media item Processing
 */
final class PreviewProcessing implements ProcessorInterface
{
    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            && ($helperRegistry = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class))->hasOnlineMediaHelper(($sourceFile = $task->getSourceFile())->getExtension())
            && ($previewImageFile = $helperRegistry->getOnlineMediaHelper($sourceFile)->getPreviewImage($sourceFile))
            && !empty($previewImageFile)
            && file_exists($previewImageFile);
    }

    public function processTask(TaskInterface $task): void
    {
        $file = $task->getSourceFile();
        GeneralUtility::makeInstance(LocalImageProcessor::class)
            ->processTaskWithLocalFile(
                $task,
                GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)
                    ->getOnlineMediaHelper($file)
                    ->getPreviewImage($file)
            );
    }
}
