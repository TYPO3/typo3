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

namespace TYPO3\CMS\Core\Resource\Processing;

use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageProcessingInstructions;
use TYPO3\CMS\Core\Imaging\SvgManipulation;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Processes (scales) SVG Images files or crops them via \DOMDocument
 * and creates a new locally created processed file which is then pushed
 * into FAL again.
 */
class SvgImageProcessor implements ProcessorInterface
{
    private int $defaultSvgDimension = 64;

    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            && $task->getTargetFileExtension() === 'svg';
    }

    /**
     * Processes the given task.
     *
     * @throws \InvalidArgumentException|InsufficientFolderReadPermissionsException
     */
    public function processTask(TaskInterface $task): void
    {
        try {
            $processingInstructions = ImageProcessingInstructions::fromProcessingTask($task);
            $imageDimension = new ImageDimension($processingInstructions->width, $processingInstructions->height);
        } catch (ZeroImageDimensionException) {
            $processingInstructions = new ImageProcessingInstructions(
                width: $this->defaultSvgDimension,
                height: $this->defaultSvgDimension,
            );
            // To not fail image processing, we just assume an SVG image dimension here
            $imageDimension = new ImageDimension(
                width: $this->defaultSvgDimension,
                height: $this->defaultSvgDimension
            );
        }

        $task->getTargetFile()->updateProperties(
            [
                'width' => $imageDimension->getWidth(),
                'height' => $imageDimension->getHeight(),
                'size' => $task->getSourceFile()->getSize(),
                'checksum' => $task->getConfigurationChecksum(),
            ]
        );

        if ($this->checkForExistingTargetFile($task)) {
            return;
        }

        $cropArea = $processingInstructions->cropArea;
        if ($cropArea === null || $cropArea->makeRelativeBasedOnFile($task->getSourceFile())->isEmpty()) {
            $task->setExecuted(true);
            $task->getTargetFile()->setUsesOriginalFile();
            return;
        }

        $this->applyCropping($task, $cropArea, $imageDimension);
    }

    /**
     * Create standalone wrapper files for SVGs.
     * Cropped responsive images delivered via an <img> tag or
     * as a URI for a background image, need to be self-contained.
     * Therefore we wrap a <svg> container around the original SVG
     * content.
     * A viewBox() crop is then applied to that container.
     * The processed file will contain all the viewBox cropping information
     * and thus transports intrinsic sizes for all variants of CSS
     * processing (max/min width/height).
     */
    protected function applyCropping(TaskInterface $task, Area $cropArea, ImageDimension $imageDimension): void
    {
        $processedSvg = GeneralUtility::makeInstance(SvgManipulation::class)->cropScaleSvgString(
            $task->getSourceFile()->getContents(),
            $cropArea,
            $imageDimension
        );
        // Save the output as a new processed file.
        $temporaryFilename = $this->getFilenameForSvgCropScaleMask($task);
        GeneralUtility::writeFile($temporaryFilename, $processedSvg->saveXML(), true);

        $task->setExecuted(true);
        $imageInformation = GeneralUtility::makeInstance(ImageInfo::class, $temporaryFilename);

        $task->getTargetFile()->setName($task->getTargetFileName());

        $task->getTargetFile()->updateProperties([
            // @todo: Use round() instead of int-cast to avoid an implicit floor()?
            'width' => (string)$imageDimension->getWidth(),
            'height' => (string)$imageDimension->getHeight(),
            'size' => $imageInformation->getSize(),
            'checksum' => $task->getConfigurationChecksum(),
        ]);
        $task->getTargetFile()->updateWithLocalFile($temporaryFilename);
        // The temporary file is removed again
        GeneralUtility::unlink_tempfile($temporaryFilename);
    }

    /**
     * Check if the target file that is to be processed already exists.
     * If it exists, use the metadata from that file and mark task as done.
     *
     * @throws InsufficientFolderReadPermissionsException
     * @todo - Refactor this 80% duplicate code of LocalImageProcessor::checkForExistingTargetFile
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
     * Returns the filename for a cropped/scaled/masked file which will be put
     * in typo3temp for the time being.
     */
    protected function getFilenameForSvgCropScaleMask(TaskInterface $task): string
    {
        $targetFileExtension = $task->getTargetFileExtension();
        return GeneralUtility::tempnam($task->getTargetFile()->generateProcessedFileNameWithoutExtension(), '.' . ltrim(trim($targetFileExtension)));
    }

}
