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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\ImageProcessingInstructions;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

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
    protected function processTaskWithLocalFile(TaskInterface $task, ?string $localFile): void
    {
        try {
            if ($task->getName() === 'CropScaleMask') {
                if ($localFile === null) {
                    $result = $this->processCropScaleMask($task);
                } else {
                    $result = $this->processCropScaleMaskWithLocalFile($task, $localFile);
                }
            } elseif ($task->getName() === 'Preview') {
                if ($localFile === null) {
                    $result = $this->processPreview($task);
                } else {
                    $result = $this->processPreviewWithLocalFile($task, $localFile);
                }
            } else {
                throw new \InvalidArgumentException('Cannot find helper for task name: "' . $task->getName() . '"', 1353401352);
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
     * Helper methods to locally perform a crop/scale/mask task with the TYPO3 image processing classes.
     */

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
     */
    protected function processCropScaleMask(TaskInterface $task): ?array
    {
        return $this->processCropScaleMaskWithLocalFile($task, $task->getSourceFile()->getForLocalProcessing(false));
    }

    /**
     * Does the heavy lifting prescribed in processTask()
     * except that the processing can be performed on any given local image.
     * Note that the resize() method usually does not upscale images (depends on "noScale" option),
     * so the original file would be used for the processor result.
     */
    protected function processCropScaleMaskWithLocalFile(TaskInterface $task, string $originalFileName): ?array
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
        $name = $this->generateProcessedFileNameWithoutExtension($task);
        return Environment::getPublicPath() . '/typo3temp/' . $name . '.' . ltrim(trim($targetFileExtension), '.');
    }

    /**
     * Generate the name of the new File. Should be placed somwhere else?
     */
    protected function generateProcessedFileNameWithoutExtension(TaskInterface $task): string
    {
        return implode('_', [
            $task->getSourceFile()->getNameWithoutExtension(),
            $task->getSourceFile()->getUid(),
            $task->getConfigurationChecksum(),
        ]);
    }

    /**
     * Helper for creating local image previews using TYPO3s image processing classes.
     */

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
     */
    protected function processPreview(TaskInterface $task): ?array
    {
        $sourceFile = $task->getSourceFile();
        $task->sanitizeConfiguration();
        $configuration = $task->getConfiguration();

        // Do not scale up, if the source file has dimensions and any target dimension (width/height) is larger
        // This is related to $TYPO3_CONF_VARS['GFX']['processor_allowUpscaling'] = false to ensure the original
        // file can be used (instead of getting processed)
        if ($sourceFile->getProperty('width') > 0 && $sourceFile->getProperty('height') > 0
            && (
                $configuration['width'] > $sourceFile->getProperty('width')
                || $configuration['height'] > $sourceFile->getProperty('height')
            )
        ) {
            return null;
        }

        return $this->generatePreviewFromFile($sourceFile, $configuration, $this->getTemporaryFilePathForPreview($task));
    }

    /**
     * Does the heavy lifting prescribed in processTask()
     * except that the processing can be performed on any given local image
     */
    protected function processPreviewWithLocalFile(TaskInterface $task, string $localFile): ?array
    {
        return $this->generatePreviewFromLocalFile($localFile, $task->getConfiguration(), $this->getTemporaryFilePathForPreview($task));
    }

    /**
     * Returns the path to a temporary file for processing
     *
     * @return non-empty-string
     */
    protected function getTemporaryFilePathForPreview(TaskInterface $task): string
    {
        return GeneralUtility::tempnam('preview_', '.' . $task->getTargetFileExtension());
    }

    /**
     * Generates a preview for a file
     *
     * @param File $file The source file
     * @param array $configuration Processing configuration
     * @param string $targetFilePath Output file path
     */
    protected function generatePreviewFromFile(File $file, array $configuration, string $targetFilePath): array
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
     */
    protected function generatePreviewFromLocalFile(string $originalFileName, array $configuration, string $targetFilePath): array
    {
        // Create the temporary file
        $imageService = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $result = $imageService->resize($originalFileName, 'WEB', $configuration['width'] . 'm', $configuration['height'] . 'm', '', ['sample' => true]);
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
