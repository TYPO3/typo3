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

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

/**
 * Helper for creating local image previews using TYPO3s image processing classes.
 */
class LocalPreviewHelper
{
    /**
     * Enforce default configuration for preview processing
     *
     * @todo This method is not needed anymore and will be deprecated (once the whole class can be removed)
     */
    public static function preProcessConfiguration(array $configuration): array
    {
        $defaultPreviewConfiguration = [
            'width' => 64,
            'height' => 64,
        ];
        $configuration = array_replace($defaultPreviewConfiguration, $configuration);
        $configuration['width'] = MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
        $configuration['height'] = MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);
        return array_filter(
            $configuration,
            static function (string|int|bool|array|null $value, string $name): bool {
                return !empty($value) && in_array($name, ['width', 'height'], true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * This method actually does the processing of files locally
     *
     * takes the original file (on remote storages this will be fetched from the remote server)
     * does the IM magic on the local server by creating a temporary typo3temp/ file
     * copies the typo3temp/ file to the processing folder of the target storage
     * removes the typo3temp/ file
     *
     * The returned array has the following structure:
     *   width => 100
     *   height => 200
     *   filePath => /some/path
     *
     * If filePath isn't set but width and height are the original file is used as ProcessedFile
     * with the returned width and height. This is for example useful for SVG images.
     *
     * @param TaskInterface $task
     * @return array|null
     */
    public function process(TaskInterface $task)
    {
        $sourceFile = $task->getSourceFile();
        $task->sanitizeConfiguration();
        $configuration = $task->getConfiguration();

        // Do not scale up if the source file has a size and the target size is larger
        if ($sourceFile->getProperty('width') > 0 && $sourceFile->getProperty('height') > 0
            && $configuration['width'] > $sourceFile->getProperty('width')
            && $configuration['height'] > $sourceFile->getProperty('height')) {
            return null;
        }

        return $this->generatePreviewFromFile($sourceFile, $configuration, $this->getTemporaryFilePath($task));
    }

    /**
     * Does the heavy lifting prescribed in processTask()
     * except that the processing can be performed on any given local image
     */
    public function processWithLocalFile(TaskInterface $task, string $localFile): ?array
    {
        return $this->generatePreviewFromLocalFile($localFile, $task->getConfiguration(), $this->getTemporaryFilePath($task));
    }

    /**
     * Returns the path to a temporary file for processing
     *
     * @return non-empty-string
     */
    protected function getTemporaryFilePath(TaskInterface $task)
    {
        return GeneralUtility::tempnam('preview_', '.' . $task->getTargetFileExtension());
    }

    /**
     * Generates a preview for a file
     *
     * @param File $file The source file
     * @param array $configuration Processing configuration
     * @param string $targetFilePath Output file path
     * @return array
     */
    protected function generatePreviewFromFile(File $file, array $configuration, string $targetFilePath)
    {
        // Check file extension
        if (!$file->isType(FileType::IMAGE) && !$file->isImage()) {
            // Create a default image
            $graphicalFunctions = GeneralUtility::makeInstance(GifBuilder::class);
            $graphicalFunctions->getTemporaryImageWithText(
                $targetFilePath,
                'Not imagefile!',
                'No ext!',
                $file->getName()
            );
            return [
                'filePath' => $targetFilePath,
            ];
        }

        return $this->generatePreviewFromLocalFile($file->getForLocalProcessing(false), $configuration, $targetFilePath);
    }

    /**
     * Generates a preview for a local file
     *
     * @param string $originalFileName Optional input file path
     * @param array $configuration Processing configuration
     * @param string $targetFilePath Output file path
     * @return array
     */
    protected function generatePreviewFromLocalFile(string $originalFileName, array $configuration, string $targetFilePath)
    {
        // Create the temporary file
        $imageService = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $result = $imageService->resize($originalFileName, 'WEB', $configuration['width'], $configuration['height'], '', ['sample' => true]);
        if ($result) {
            $targetFilePath = $result->getRealPath();
        }
        if (!file_exists($targetFilePath)) {
            // Create an error gif
            $graphicalFunctions = GeneralUtility::makeInstance(GifBuilder::class);
            $graphicalFunctions->getTemporaryImageWithText(
                $targetFilePath,
                'No thumb',
                'generated!'
            );
        }

        return [
            'filePath' => $targetFilePath,
        ];
    }
}
