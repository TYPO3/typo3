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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\ImageProcessingInstructions;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class to locally perform a crop/scale/mask task with the TYPO3 image processing classes.
 */
class LocalCropScaleMaskHelper
{
    /**
     * This method actually does the processing of files locally
     *
     * Takes the original file (for remote storages this will be fetched from the remote server),
     * does the IM magic on the local server by creating a temporary typo3temp/ file,
     * copies the typo3temp/ file to the processing folder of the target storage and
     * removes the typo3temp/ file.
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
        return $this->processWithLocalFile($task, $task->getSourceFile()->getForLocalProcessing(false));
    }

    /**
     * Does the heavy lifting prescribed in processTask()
     * except that the processing can be performed on any given local image
     */
    public function processWithLocalFile(TaskInterface $task, string $originalFileName): ?array
    {
        $result = null;
        $targetFile = $task->getTargetFile();
        $targetFileExtension = $task->getTargetFileExtension();

        $imageOperations = GeneralUtility::makeInstance(GraphicalFunctions::class);

        $configuration = $targetFile->getProcessingConfiguration();
        $configuration['additionalParameters'] ??= '';

        // Normal situation (no masking) - just scale the image
        if (!is_array($configuration['maskImages'] ?? null)) {
            // the result info is an array with 0=width,1=height,2=extension,3=filename
            $result = $imageOperations->resize(
                $originalFileName,
                $targetFileExtension,
                $configuration['width'] ?? '',
                $configuration['height'] ?? '',
                $configuration['additionalParameters'],
                $configuration,
            );
        } else {
            $temporaryFileName = $this->getFilenameForImageCropScaleMask($task);
            $maskImage = $configuration['maskImages']['maskImage'] ?? null;
            $maskBackgroundImage = $configuration['maskImages']['backgroundImage'];
            if ($maskImage instanceof FileInterface && $maskBackgroundImage instanceof FileInterface) {
                // This converts the original image to a temporary PNG file during all steps of the masking process
                $tempFileInfo = $imageOperations->resize(
                    $originalFileName,
                    'png',
                    $configuration['width'] ?? '',
                    $configuration['height'] ?? '',
                    $configuration['additionalParameters'],
                    $configuration
                );
                if ($tempFileInfo !== null) {
                    // Scaling
                    $command = '-geometry ' . $tempFileInfo->getWidth() . 'x' . $tempFileInfo->getHeight() . '!';
                    $imageOperations->mask(
                        $tempFileInfo->getRealPath(),
                        $temporaryFileName,
                        $maskImage->getForLocalProcessing(),
                        $maskBackgroundImage->getForLocalProcessing(),
                        $command,
                        $configuration
                    );
                    $maskBottomImage = $configuration['maskImages']['maskBottomImage'] ?? null;
                    $maskBottomImageMask = $configuration['maskImages']['maskBottomImageMask'] ?? null;
                    if ($maskBottomImage instanceof FileInterface && $maskBottomImageMask instanceof FileInterface) {
                        // Uses the temporary PNG file from the previous step and applies another mask
                        $imageOperations->mask(
                            $temporaryFileName,
                            $temporaryFileName,
                            $maskBottomImage->getForLocalProcessing(),
                            $maskBottomImageMask->getForLocalProcessing(),
                            $command,
                            $configuration
                        );
                    }
                }
                $result = $tempFileInfo;
            }
        }

        // check if the processing really generated a new file (scaled and/or cropped)
        if ($result !== null) {
            // The file processing yielded a different file extension than we anticipated. Most likely because
            // the processing service found out a file type needed to use fallback storage. In this case, we
            // append the actually received file extension to our file to be stored, which will also hint at
            // a failed conversion, like some-file.avif.jpg. Otherwise use the same file extension. This is
            // evaluated for persistence in @see LocalImageProcessor->processTaskWithLocalFile().
            $remapProcessedTargetFileExtension = ($targetFileExtension !== $result->getExtension())
                // Remap to correct image type extension.
                ? $result->getExtension()
                // No file extension remap required.
                : null;
            // @todo: realpath handling should be revisited, they may produce issues
            //        with open_basedir restrictions and/or lockRootPath.
            if ($result->getRealPath() !== realpath($originalFileName)) {
                $result = [
                    'width' => $result->getWidth(),
                    'height' => $result->getHeight(),
                    'filePath' => $result->getRealPath(),
                    'remapProcessedTargetFileExtension' => $remapProcessedTargetFileExtension,
                ];
            } else {
                // No file was generated
                $result = null;
            }
        }

        // If noScale option is applied, we need to reset the width and height to ensure the scaled values
        // are used for the generated image tag even if the image itself is not scaled. This is needed, as
        // the result is discarded due to the fact that the original image is used.
        // @see https://forge.typo3.org/issues/100972
        // Note: This should only happen if no image has been generated ($result === null).
        if ($result === null && ($configuration['noScale'] ?? false)) {
            $configuration = $task->getConfiguration();
            $localProcessedFile = $task->getSourceFile()->getForLocalProcessing(false);
            $imageDimensions = $imageOperations->getImageDimensions($localProcessedFile, true);
            $imageScaleInfo = ImageProcessingInstructions::fromCropScaleValues(
                $imageDimensions->getWidth(),
                $imageDimensions->getHeight(),
                $configuration['width'] ?? '',
                $configuration['height'] ?? '',
                $configuration
            );
            $targetFile->updateProperties([
                'width' => $imageScaleInfo->width,
                'height' => $imageScaleInfo->height,
            ]);
        }

        return $result;
    }

    /**
     * Returns the filename for a cropped/scaled/masked file which will be put in typo3temp for the time being.
     */
    protected function getFilenameForImageCropScaleMask(TaskInterface $task): string
    {
        $targetFileExtension = $task->getTargetFileExtension();
        return Environment::getPublicPath() . '/typo3temp/' . $task->getTargetFile()->generateProcessedFileNameWithoutExtension() . '.' . ltrim(trim($targetFileExtension), '.');
    }
}
