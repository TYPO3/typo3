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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\Exception\InvalidSvgException;
use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageProcessingInstructions;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentFactory;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Processes (scales) SVG Images files or crops them via \DOMDocument
 * and creates a new locally created processed file which is then pushed
 * into FAL again.
 */
#[Autoconfigure(public: true)]
readonly class SvgImageProcessor implements ResourceAwareProcessorInterface
{
    private const int DEFAULT_SVG_DIMENSION = 64;

    public function __construct(
        private SvgDocumentFactory $svgDocumentFactory,
        private SvgDocumentService $svgDocumentService,
    ) {}

    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            && $task->getTargetFileExtension() === 'svg';
    }

    /**
     * Some call sites (checkForExistingTargetFile()) are genuinely FAL-only - there is
     * no non-FAL equivalent of a ResourceStorage processing folder. Narrow back to a
     * concrete File/ProcessedFile there, rather than at every such call site.
     */
    private function getConcreteSourceFile(TaskInterface $task): File
    {
        $sourceFile = $task->getSourceFile();
        if (!$sourceFile instanceof File) {
            throw new \InvalidArgumentException(sprintf('%s can only process a FAL File.', self::class), 1789624492);
        }
        return $sourceFile;
    }

    private function getConcreteTargetFile(TaskInterface $task): ProcessedFile
    {
        $targetFile = $task->getTargetFile();
        if (!$targetFile instanceof ProcessedFile) {
            throw new \InvalidArgumentException(sprintf('%s can only process a FAL ProcessedFile.', self::class), 1789624493);
        }
        return $targetFile;
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
                width: self::DEFAULT_SVG_DIMENSION,
                height: self::DEFAULT_SVG_DIMENSION,
            );
            // To not fail image processing, we just assume an SVG image dimension here
            $imageDimension = new ImageDimension(
                width: self::DEFAULT_SVG_DIMENSION,
                height: self::DEFAULT_SVG_DIMENSION
            );
        }

        $sourceFile = $task->getSourceFile();
        $task->getTargetFile()->updateProperties(
            [
                'width' => $imageDimension->getWidth(),
                'height' => $imageDimension->getHeight(),
                'size' => $sourceFile instanceof File ? $sourceFile->getSize() : strlen($sourceFile->getContents()),
                'checksum' => $task->getConfigurationChecksum(),
            ]
        );

        // A non-FAL target is only ever handed to processTask() once
        // FileProcessingService already confirmed it needs processing, so unlike
        // checkForExistingTargetFile() below, there is no reuse check to repeat here.
        if ($task->getTargetFile() instanceof ProcessedFile && $this->checkForExistingTargetFile($task)) {
            return;
        }

        $cropArea = $processingInstructions->cropArea;
        if ($cropArea === null) {
            $task->setExecuted(true);
            $task->getTargetFile()->setUsesOriginalFile();
            return;
        }
        $relativeCropArea = $sourceFile instanceof File
            ? $cropArea->makeRelativeBasedOnFile($sourceFile)
            : $cropArea->makeRelativeBasedOnDimension($sourceFile->getImageDimension());
        if ($relativeCropArea->isEmpty()) {
            $task->setExecuted(true);
            $task->getTargetFile()->setUsesOriginalFile();
            return;
        }

        $this->applyCropping($task, $cropArea, $imageDimension);
    }

    /**
     * Wrap the source SVG in a crop container and write it to a temporary
     * processed file. The wrapper carries the viewBox crop and target
     * dimensions so the result is self-contained when embedded via <img>.
     */
    protected function applyCropping(TaskInterface $task, Area $cropArea, ImageDimension $imageDimension): void
    {
        try {
            $sourceFile = $task->getSourceFile();
            // Keep passing the File object itself for the FAL case unchanged - it reads
            // through the storage driver, which is not necessarily the same as a locally
            // cached copy for a remote storage. fromFile() reads a plain local path for
            // that case; a non-FAL source has none to give it, but does not need one
            // either - fromString() is exactly what fromFile() delegates to internally.
            $document = $sourceFile instanceof File
                ? $this->svgDocumentFactory->fromFile($sourceFile)
                : $this->svgDocumentFactory->fromString($sourceFile->getContents());
            $processedSvg = $this->svgDocumentService->cropScale($document, $cropArea, $imageDimension);
        } catch (InvalidSvgException) {
            // Source SVG could not be parsed - fall back to the unprocessed original.
            $task->setExecuted(true);
            $task->getTargetFile()->setUsesOriginalFile();
            return;
        }
        $temporaryFilename = $this->getFilenameForSvgCropScaleMask($task);
        GeneralUtility::writeFile($temporaryFilename, $this->svgDocumentService->toXml($processedSvg), true);

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
        $targetFile = $this->getConcreteTargetFile($task);
        $storage = $targetFile->getStorage();
        $processingFolder = $storage->getProcessingFolder($this->getConcreteSourceFile($task));

        // explicitly check for the raw filename here, as we check for files that existed before we even started
        // processing, i.e. that were processed earlier
        if ($processingFolder->hasFile($task->getTargetFileName())) {
            // When the processed file already exists set it as processed file
            $targetFile->setName($task->getTargetFileName());

            // If the processed file is stored on a remote server, we must fetch a local copy of the file, as we
            // have no API for fetching file metadata from a remote file.
            $localProcessedFile = $storage->getFileForLocalProcessing($targetFile, false);
            $task->setExecuted(true);
            $imageInformation = GeneralUtility::makeInstance(ImageInfo::class, $localProcessedFile);
            $properties = [
                'width' => $imageInformation->getWidth(),
                'height' => $imageInformation->getHeight(),
                'size' => $imageInformation->getSize(),
                'checksum' => $task->getConfigurationChecksum(),
            ];
            $targetFile->updateProperties($properties);

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
        return GeneralUtility::tempnam($this->generateProcessedFileNameWithoutExtension($task), '.' . ltrim(trim($targetFileExtension)));
    }

    /**
     * Generate the name of the new File. Should be placed somwhere else?
     */
    protected function generateProcessedFileNameWithoutExtension(TaskInterface $task): string
    {
        $sourceFile = $task->getSourceFile();
        return implode('_', [
            $sourceFile->getNameWithoutExtension(),
            $sourceFile instanceof File ? $sourceFile->getUid() : md5($sourceFile->getResourceIdentifier()),
            $task->getConfigurationChecksum(),
        ]);
    }
}
