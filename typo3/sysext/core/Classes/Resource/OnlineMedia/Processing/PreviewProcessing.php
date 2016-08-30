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

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Service\FileProcessingService;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
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
     * @param AbstractDriver $driver
     * @param ProcessedFile $processedFile
     * @param File $file
     * @param string $taskType
     * @param array $configuration
     */
    public function processFile(FileProcessingService $fileProcessingService, AbstractDriver $driver, ProcessedFile $processedFile, File $file, $taskType, array $configuration)
    {
        if ($taskType !== 'Image.Preview' && $taskType !== 'Image.CropScaleMask') {
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
        $temporaryFileNameForResizedThumb = uniqid(PATH_site . 'typo3temp/online_media_' . $file->getHashedIdentifier()) . '.jpg';
        switch ($taskType) {
            case 'Image.Preview':
                // Merge custom configuration with default configuration
                $configuration = array_merge(['width' => 64, 'height' => 64], $configuration);
                $configuration['width'] = MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
                $configuration['height'] = MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);
                $this->resizeImage($temporaryFileName, $temporaryFileNameForResizedThumb, $configuration);
                break;

            case 'Image.CropScaleMask':
                $this->cropScaleImage($temporaryFileName, $temporaryFileNameForResizedThumb, $configuration);
                break;
        }
        if (is_file($temporaryFileNameForResizedThumb)) {
            $processedFile->setName($this->getTargetFileName($processedFile));
            list($width, $height) = getimagesize($temporaryFileNameForResizedThumb);
            $processedFile->updateProperties(
                [
                    'width' => $width,
                    'height' => $height,
                    'size' => filesize($temporaryFileNameForResizedThumb),
                    'checksum' => $processedFile->getTask()->getConfigurationChecksum()
                ]
            );
            $processedFile->updateWithLocalFile($temporaryFileNameForResizedThumb);

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
        if (empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['im'])) {
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

            $cmd = GeneralUtility::imageMagickCommand('convert', $parameters) . ' 2>&1';
            CommandUtility::exec($cmd);
        }

        if (!file_exists($temporaryFileName)) {
            // Create a error image
            $graphicalFunctions = $this->getGraphicalFunctionsObject();
            $graphicalFunctions->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', basename($originalFileName));
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
            /** @var $gifBuilder GifBuilder */
            $gifBuilder = GeneralUtility::makeInstance(GifBuilder::class);
            $gifBuilder->init();

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
            $graphicalFunctions->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', basename($originalFileName));
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
    protected function getGraphicalFunctionsObject()
    {
        static $graphicalFunctionsObject = null;
        if ($graphicalFunctionsObject === null) {
            $graphicalFunctionsObject = GeneralUtility::makeInstance(GraphicalFunctions::class);
        }
        return $graphicalFunctionsObject;
    }
}
