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

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Representing an image dimension (width and height)
 * and calculating the dimension from a source with a given processing instruction
 */
class ImageDimension
{
    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public static function fromProcessingTask(TaskInterface $task): self
    {
        $config = self::getConfigurationForImageCropScaleMask($task);
        $processedFile = $task->getTargetFile();
        $isCropped = false;
        if (($config['crop'] ?? null) instanceof Area) {
            $isCropped = true;
            $imageDimension = new self(
                (int)round($config['crop']->getWidth()),
                (int)round($config['crop']->getHeight())
            );
        } else {
            $imageDimension = new self(
                (int)$processedFile->getOriginalFile()->getProperty('width'),
                (int)$processedFile->getOriginalFile()->getProperty('height')
            );
        }
        if ($imageDimension->width <=0 || $imageDimension->height <=0) {
            throw new \BadMethodCallException('Width and height of the image must be greater than zero', 1597310560);
        }
        $result = GeneralUtility::makeInstance(GraphicalFunctions::class)->getImageScale(
            [
                $imageDimension->width,
                $imageDimension->height,
                $processedFile->getExtension(),
            ],
            $config['width'] ?? null,
            $config['height'] ?? null,
            $config
        );
        $imageWidth = $geometryWidth = (int)$result[0];
        $imageHeight = $geometryHeight = (int)$result[1];
        $isCropScaled = $result['crs'];

        if ($isCropScaled) {
            $cropWidth = (int)$result['origW'];
            $cropHeight = (int)$result['origH'];
            // If the image is crop scaled, use the dimension of the crop
            // unless crop area exceeds the dimension of the scaled image
            if ($cropWidth <= $geometryWidth && $cropHeight <= $geometryHeight) {
                $imageWidth = $cropWidth;
                $imageHeight = $cropHeight;
            }
            if (!$isCropped && $task->getTargetFileExtension() === 'svg') {
                // Keep aspect ratio of SVG files, when crop-scaling is requested
                // but no crop is applied
                if ($geometryWidth > $geometryHeight) {
                    $imageHeight = (int)round($imageWidth * $geometryHeight / $geometryWidth);
                } else {
                    $imageWidth = (int)round($imageHeight * $geometryWidth / $geometryHeight);
                }
            }
        }
        $imageDimension->width = $imageWidth;
        $imageDimension->height = $imageHeight;

        return $imageDimension;
    }

    private static function getConfigurationForImageCropScaleMask(TaskInterface $task): array
    {
        $configuration = $task->getConfiguration();

        if ($task->getTargetFile()->getTaskIdentifier() === ProcessedFile::CONTEXT_IMAGEPREVIEW) {
            $configuration = LocalPreviewHelper::preProcessConfiguration($configuration);
            $configuration['maxWidth'] = $configuration['width'];
            unset($configuration['width']);
            $configuration['maxHeight'] = $configuration['height'];
            unset($configuration['height']);
        }

        $options = $configuration;
        if ($configuration['maxWidth'] ?? null) {
            $options['maxW'] = $configuration['maxWidth'];
        }
        if ($configuration['maxHeight'] ?? null) {
            $options['maxH'] = $configuration['maxHeight'];
        }
        if ($configuration['minWidth'] ?? null) {
            $options['minW'] = $configuration['minWidth'];
        }
        if ($configuration['minHeight'] ?? null) {
            $options['minH'] = $configuration['minHeight'];
        }
        if ($configuration['crop'] ?? null) {
            $options['crop'] = $configuration['crop'];
            if (is_string($configuration['crop'])) {
                // check if it is a json object
                $cropData = json_decode($configuration['crop']);
                if ($cropData) {
                    $options['crop'] = new Area($cropData->x, $cropData->y, $cropData->width, $cropData->height);
                } else {
                    [$offsetLeft, $offsetTop, $newWidth, $newHeight] = explode(',', $configuration['crop'], 4);
                    $options['crop'] = new Area((float)$offsetLeft, (float)$offsetTop, (float)$newWidth, (float)$newHeight);
                }
                if ($options['crop']->isEmpty()) {
                    unset($options['crop']);
                }
            }
        }
        if ($configuration['noScale'] ?? null) {
            $options['noScale'] = $configuration['noScale'];
        }

        return $options;
    }
}
