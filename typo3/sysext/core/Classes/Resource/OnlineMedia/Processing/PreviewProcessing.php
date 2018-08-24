<?php
namespace TYPO3\CMS\Core\Resource\OnlineMedia\Processing;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Service\FileProcessingService;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

/**
 * Preview of Online Media item Processing
 */
class PreviewProcessing
{
    /**
     * @var LocalImageProcessor
     */
    protected $processor;

    /**
     * @param ProcessedFile $processedFile
     * @return bool
     */
    protected function needsReprocessing($processedFile)
    {
        return $processedFile->isNew()
            || (!$processedFile->usesOriginalFile() && !$processedFile->exists())
            || $processedFile->isOutdated();
    }

    /**
     * Process file
     * Create static image preview for Online Media item when possible
     *
     * @param FileProcessingService $fileProcessingService
     * @param DriverInterface $driver
     * @param ProcessedFile $processedFile
     * @param File $file
     * @param string $taskType
     * @param array $configuration
     */
    public function processFile(FileProcessingService $fileProcessingService, DriverInterface $driver, ProcessedFile $processedFile, File $file, $taskType, array $configuration)
    {
        if ($taskType !== ProcessedFile::CONTEXT_IMAGEPREVIEW && $taskType !== ProcessedFile::CONTEXT_IMAGECROPSCALEMASK) {
            return;
        }
        // Check if processing is needed
        if (!$this->needsReprocessing($processedFile)) {
            return;
        }
        // Check if there is a OnlineMediaHelper registered for this file type
        $helper = OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($file);
        if ($helper === false) {
            return;
        }
        // Check if helper provides a preview image
        $temporaryFileName = $helper->getPreviewImage($file);
        if (empty($temporaryFileName) || !file_exists($temporaryFileName)) {
            return;
        }
        $temporaryFileNameForResizedThumb = uniqid(Environment::getVarPath() . '/transient/online_media_' . $file->getHashedIdentifier()) . '.jpg';
        $configuration = $processedFile->getProcessingConfiguration();
        switch ($taskType) {
            case ProcessedFile::CONTEXT_IMAGEPREVIEW:
                $this->resizeImage($temporaryFileName, $temporaryFileNameForResizedThumb, $configuration);
                break;

            case ProcessedFile::CONTEXT_IMAGECROPSCALEMASK:
                $this->cropScaleImage($temporaryFileName, $temporaryFileNameForResizedThumb, $configuration);
                break;
        }
        GeneralUtility::unlink_tempfile($temporaryFileName);
        if (is_file($temporaryFileNameForResizedThumb)) {
            $processedFile->setName($this->getTargetFileName($processedFile));
            $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $temporaryFileNameForResizedThumb);
            $processedFile->updateProperties(
                [
                    'width' => $imageInfo->getWidth(),
                    'height' => $imageInfo->getHeight(),
                    'size' => filesize($temporaryFileNameForResizedThumb),
                    'checksum' => $processedFile->getTask()->getConfigurationChecksum()
                ]
            );
            $processedFile->updateWithLocalFile($temporaryFileNameForResizedThumb);
            GeneralUtility::unlink_tempfile($temporaryFileNameForResizedThumb);

            /** @var ProcessedFileRepository $processedFileRepository */
            $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
            $processedFileRepository->add($processedFile);
        }
    }

    /**
     * @param ProcessedFile $processedFile
     * @param string $prefix
     * @return string
     */
    protected function getTargetFileName(ProcessedFile $processedFile, $prefix = 'preview_')
    {
        return $prefix . $processedFile->getTask()->getConfigurationChecksum() . '_' . $processedFile->getOriginalFile()->getNameWithoutExtension() . '.jpg';
    }

    /**
     * @param string $originalFileName
     * @param string $temporaryFileName
     * @param array $configuration
     */
    protected function resizeImage($originalFileName, $temporaryFileName, $configuration)
    {
        // Create the temporary file
        if (empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'])) {
            return;
        }

        if (file_exists($originalFileName)) {
            $arguments = CommandUtility::escapeShellArguments([
                'width' => $configuration['width'],
                'height' => $configuration['height'],
                'originalFileName' => $originalFileName,
                'temporaryFileName' => $temporaryFileName,
            ]);
            $parameters = '-sample ' . $arguments['width'] . 'x' . $arguments['height'] . ' '
                . $arguments['originalFileName'] . '[0] ' . $arguments['temporaryFileName'];

            $cmd = CommandUtility::imageMagickCommand('convert', $parameters) . ' 2>&1';
            CommandUtility::exec($cmd);
        }

        if (!file_exists($temporaryFileName)) {
            // Create a error image
            $graphicalFunctions = $this->getGraphicalFunctionsObject();
            $graphicalFunctions->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', PathUtility::basename($originalFileName));
        }
    }

    /**
     * cropScaleImage
     *
     * @param string $originalFileName
     * @param string $temporaryFileName
     * @param array $configuration
     */
    protected function cropScaleImage($originalFileName, $temporaryFileName, $configuration)
    {
        if (file_exists($originalFileName)) {
            $gifBuilder = GeneralUtility::makeInstance(GifBuilder::class);

            $options = $this->getConfigurationForImageCropScaleMask($configuration, $gifBuilder);
            $info = $gifBuilder->getImageDimensions($originalFileName);
            $data = $gifBuilder->getImageScale($info, $configuration['width'], $configuration['height'], $options);

            $info[0] = $data[0];
            $info[1] = $data[1];
            $frame = '';
            $params = $gifBuilder->cmds['jpg'];

            // Cropscaling:
            if ($data['crs']) {
                if (!$data['origW']) {
                    $data['origW'] = $data[0];
                }
                if (!$data['origH']) {
                    $data['origH'] = $data[1];
                }
                $offsetX = (int)(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
                $offsetY = (int)(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200);
                $params .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . '! ';
            }
            $command = $gifBuilder->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
            $gifBuilder->imageMagickExec($originalFileName, $temporaryFileName, $command, $frame);
        }
        if (!file_exists($temporaryFileName)) {
            // Create a error image
            $graphicalFunctions = $this->getGraphicalFunctionsObject();
            $graphicalFunctions->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', PathUtility::basename($originalFileName));
        }
    }

    /**
     * Get configuration for ImageCropScaleMask processing
     *
     * @param array $configuration
     * @param GifBuilder $gifBuilder
     * @return array
     */
    protected function getConfigurationForImageCropScaleMask(array $configuration, GifBuilder $gifBuilder)
    {
        if (!empty($configuration['useSample'])) {
            $gifBuilder->scalecmd = '-sample';
        }
        $options = [];
        if (!empty($configuration['maxWidth'])) {
            $options['maxW'] = $configuration['maxWidth'];
        }
        if (!empty($configuration['maxHeight'])) {
            $options['maxH'] = $configuration['maxHeight'];
        }
        if (!empty($configuration['minWidth'])) {
            $options['minW'] = $configuration['minWidth'];
        }
        if (!empty($configuration['minHeight'])) {
            $options['minH'] = $configuration['minHeight'];
        }

        $options['noScale'] = $configuration['noScale'];

        return $options;
    }

    /**
     * @return LocalImageProcessor
     */
    protected function getProcessor()
    {
        if (!$this->processor) {
            $this->processor = GeneralUtility::makeInstance(LocalImageProcessor::class);
        }
        return $this->processor;
    }

    /**
     * @return GraphicalFunctions
     */
    protected function getGraphicalFunctionsObject(): GraphicalFunctions
    {
        return GeneralUtility::makeInstance(GraphicalFunctions::class);
    }
}
