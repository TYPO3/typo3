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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;

/**
 * Helper class to locally perform a crop/scale/mask task with the TYPO3 image processing classes.
 */
class LocalCropScaleMaskHelper
{
    /**
     * @var LocalImageProcessor
     */
    protected $processor;

    /**
     * @param LocalImageProcessor $processor
     */
    public function __construct(LocalImageProcessor $processor)
    {
        $this->processor = $processor;
    }

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
        $result = null;
        $targetFile = $task->getTargetFile();
        $sourceFile = $task->getSourceFile();

        $originalFileName = $sourceFile->getForLocalProcessing(false);
        $gifBuilder = GeneralUtility::makeInstance(GifBuilder::class);

        $configuration = $targetFile->getProcessingConfiguration();
        $configuration['additionalParameters'] = $this->modifyImageMagickStripProfileParameters($configuration['additionalParameters'], $configuration);

        if (empty($configuration['fileExtension'])) {
            $configuration['fileExtension'] = $task->getTargetFileExtension();
        }

        $options = $this->getConfigurationForImageCropScaleMask($targetFile, $gifBuilder);

        $croppedImage = null;
        if (!empty($configuration['crop'])) {

            // check if it is a json object
            $cropData = json_decode($configuration['crop']);
            if ($cropData) {
                $crop = implode(',', [(int)$cropData->x, (int)$cropData->y, (int)$cropData->width, (int)$cropData->height]);
            } else {
                $crop = $configuration['crop'];
            }

            list($offsetLeft, $offsetTop, $newWidth, $newHeight) = explode(',', $crop, 4);

            $backupPrefix = $gifBuilder->filenamePrefix;
            $gifBuilder->filenamePrefix = 'crop_';

            $jpegQuality = MathUtility::forceIntegerInRange($GLOBALS['TYPO3_CONF_VARS']['GFX']['jpg_quality'], 10, 100, 85);

            // the result info is an array with 0=width,1=height,2=extension,3=filename
            $result = $gifBuilder->imageMagickConvert(
                $originalFileName,
                $configuration['fileExtension'],
                '',
                '',
                sprintf('-crop %dx%d+%d+%d +repage -quality %d', $newWidth, $newHeight, $offsetLeft, $offsetTop, $jpegQuality),
                '',
                ['noScale' => true],
                true
            );
            $gifBuilder->filenamePrefix = $backupPrefix;

            if ($result !== null) {
                $originalFileName = $croppedImage = $result[3];
            }
        }

        // Normal situation (no masking)
        if (!(is_array($configuration['maskImages']) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'])) {

            // SVG
            if ($croppedImage === null && $sourceFile->getExtension() === 'svg') {
                $newDimensions = $this->getNewSvgDimensions($sourceFile, $configuration, $options, $gifBuilder);
                $result = [
                    0 => $newDimensions['width'],
                    1 => $newDimensions['height'],
                    3 => '' // no file = use original
                ];
            } else {
                // all other images
                // the result info is an array with 0=width,1=height,2=extension,3=filename
                $result = $gifBuilder->imageMagickConvert(
                    $originalFileName,
                    $configuration['fileExtension'],
                    $configuration['width'],
                    $configuration['height'],
                    $configuration['additionalParameters'],
                    $configuration['frame'],
                    $options
                );
            }
        } else {
            $targetFileName = $this->getFilenameForImageCropScaleMask($task);
            $temporaryFileName = Environment::getPublicPath() . '/typo3temp/' . $targetFileName;
            $maskImage = $configuration['maskImages']['maskImage'];
            $maskBackgroundImage = $configuration['maskImages']['backgroundImage'];
            if ($maskImage instanceof Resource\FileInterface && $maskBackgroundImage instanceof Resource\FileInterface) {
                $temporaryExtension = 'png';
                if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowTemporaryMasksAsPng']) {
                    // If ImageMagick version 5+
                    $temporaryExtension = $gifBuilder->gifExtension;
                }
                $tempFileInfo = $gifBuilder->imageMagickConvert(
                    $originalFileName,
                    $temporaryExtension,
                    $configuration['width'],
                    $configuration['height'],
                    $configuration['additionalParameters'],
                    $configuration['frame'],
                    $options
                );
                if (is_array($tempFileInfo)) {
                    $maskBottomImage = $configuration['maskImages']['maskBottomImage'];
                    if ($maskBottomImage instanceof Resource\FileInterface) {
                        $maskBottomImageMask = $configuration['maskImages']['maskBottomImageMask'];
                    } else {
                        $maskBottomImageMask = null;
                    }

                    //	Scaling:	****
                    $tempScale = [];
                    $command = '-geometry ' . $tempFileInfo[0] . 'x' . $tempFileInfo[1] . '!';
                    $command = $this->modifyImageMagickStripProfileParameters($command, $configuration);
                    $tmpStr = $gifBuilder->randomName();
                    //	m_mask
                    $tempScale['m_mask'] = $tmpStr . '_mask.' . $temporaryExtension;
                    $gifBuilder->imageMagickExec($maskImage->getForLocalProcessing(true), $tempScale['m_mask'], $command);
                    //	m_bgImg
                    $tempScale['m_bgImg'] = $tmpStr . '_bgImg.miff';
                    $gifBuilder->imageMagickExec($maskBackgroundImage->getForLocalProcessing(), $tempScale['m_bgImg'], $command);
                    //	m_bottomImg / m_bottomImg_mask
                    if ($maskBottomImage instanceof Resource\FileInterface && $maskBottomImageMask instanceof Resource\FileInterface) {
                        $tempScale['m_bottomImg'] = $tmpStr . '_bottomImg.' . $temporaryExtension;
                        $gifBuilder->imageMagickExec($maskBottomImage->getForLocalProcessing(), $tempScale['m_bottomImg'], $command);
                        $tempScale['m_bottomImg_mask'] = ($tmpStr . '_bottomImg_mask.') . $temporaryExtension;
                        $gifBuilder->imageMagickExec($maskBottomImageMask->getForLocalProcessing(), $tempScale['m_bottomImg_mask'], $command);
                        // BEGIN combining:
                        // The image onto the background
                        $gifBuilder->combineExec($tempScale['m_bgImg'], $tempScale['m_bottomImg'], $tempScale['m_bottomImg_mask'], $tempScale['m_bgImg']);
                    }
                    // The image onto the background
                    $gifBuilder->combineExec($tempScale['m_bgImg'], $tempFileInfo[3], $tempScale['m_mask'], $temporaryFileName);
                    $tempFileInfo[3] = $temporaryFileName;
                    // Unlink the temp-images...
                    foreach ($tempScale as $tempFile) {
                        if (@is_file($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                }
                $result = $tempFileInfo;
            }
        }

        // check if the processing really generated a new file (scaled and/or cropped)
        if ($result !== null) {
            if ($result[3] !== $originalFileName || $originalFileName === $croppedImage) {
                $result = [
                    'width' => $result[0],
                    'height' => $result[1],
                    'filePath' => $result[3],
                ];
            } else {
                // No file was generated
                $result = null;
            }
        }

        // Cleanup temp file if it isn't used as result
        if ($croppedImage && ($result === null || $croppedImage !== $result['filePath'])) {
            GeneralUtility::unlink_tempfile($croppedImage);
        }

        return $result;
    }

    /**
     * Calculate new dimensions for SVG image
     * No cropping, if cropped info present image is scaled down
     *
     * @param Resource\FileInterface $file
     * @param array $configuration
     * @param array $options
     * @param GifBuilder $gifBuilder
     * @return array width,height
     */
    protected function getNewSvgDimensions($file, array $configuration, array $options, GifBuilder $gifBuilder)
    {
        $info = [$file->getProperty('width'), $file->getProperty('height')];
        $data = $gifBuilder->getImageScale($info, $configuration['width'], $configuration['height'], $options);

        // Turn cropScaling into scaling
        if ($data['crs']) {
            if (!$data['origW']) {
                $data['origW'] = $data[0];
            }
            if (!$data['origH']) {
                $data['origH'] = $data[1];
            }
            if ($data[0] > $data['origW']) {
                $data[1] = (int)(($data['origW'] * $data[1]) / $data[0]);
                $data[0] = $data['origW'];
            } else {
                $data[0] = (int)(($data['origH'] * $data[0]) / $data[1]);
                $data[1] = $data['origH'];
            }
        }

        return [
            'width' => $data[0],
            'height' => $data[1]
        ];
    }

    /**
     * @param Resource\ProcessedFile $processedFile
     * @param \TYPO3\CMS\Frontend\Imaging\GifBuilder $gifBuilder
     *
     * @return array
     */
    protected function getConfigurationForImageCropScaleMask(Resource\ProcessedFile $processedFile, \TYPO3\CMS\Frontend\Imaging\GifBuilder $gifBuilder)
    {
        $configuration = $processedFile->getProcessingConfiguration();

        if ($configuration['useSample']) {
            $gifBuilder->scalecmd = '-sample';
        }
        $options = [];
        if ($configuration['maxWidth']) {
            $options['maxW'] = $configuration['maxWidth'];
        }
        if ($configuration['maxHeight']) {
            $options['maxH'] = $configuration['maxHeight'];
        }
        if ($configuration['minWidth']) {
            $options['minW'] = $configuration['minWidth'];
        }
        if ($configuration['minHeight']) {
            $options['minH'] = $configuration['minHeight'];
        }

        $options['noScale'] = $configuration['noScale'];

        return $options;
    }

    /**
     * Returns the filename for a cropped/scaled/masked file.
     *
     * @param TaskInterface $task
     * @return string
     */
    protected function getFilenameForImageCropScaleMask(TaskInterface $task)
    {
        $configuration = $task->getTargetFile()->getProcessingConfiguration();
        $targetFileExtension = $task->getSourceFile()->getExtension();
        $processedFileExtension = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'] ? 'png' : 'gif';
        if (is_array($configuration['maskImages']) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'] && $task->getSourceFile()->getExtension() != $processedFileExtension) {
            $targetFileExtension = 'jpg';
        } elseif ($configuration['fileExtension']) {
            $targetFileExtension = $configuration['fileExtension'];
        }

        return $task->getTargetFile()->generateProcessedFileNameWithoutExtension() . '.' . ltrim(trim($targetFileExtension), '.');
    }

    /**
     * Modifies the parameters for ImageMagick for stripping of profile information.
     *
     * @param string $parameters The parameters to be modified (if required)
     * @param array $configuration The TypoScript configuration of [IMAGE].file
     * @return string
     */
    protected function modifyImageMagickStripProfileParameters($parameters, array $configuration)
    {
        // Strips profile information of image to save some space:
        if (isset($configuration['stripProfile'])) {
            if (
                $configuration['stripProfile']
                && $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileCommand'] !== ''
            ) {
                $parameters = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripProfileCommand'] . $parameters;
            } else {
                $parameters .= '###SkipStripProfile###';
            }
        }
        return $parameters;
    }
}
