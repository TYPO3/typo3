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

use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

/**
 * Representing an image dimension (width and height)
 * and calculating the dimension from a source with a given processing instruction
 */
class ImageDimension
{
    /**
     * @param int<0, max> $width
     * @param int<0, max> $height
     */
    public function __construct(
        private readonly int $width,
        private readonly int $height
    ) {
    }

    /**
     * @return int<0, max>
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int<0, max>
     */
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
            $imageWidth = (int)round($config['crop']->getWidth());
            $imageHeight = (int)round($config['crop']->getHeight());
        } else {
            $imageWidth = (int)$processedFile->getOriginalFile()->getProperty('width');
            $imageHeight = (int)$processedFile->getOriginalFile()->getProperty('height');
        }
        if ($imageWidth <= 0 || $imageHeight <= 0) {
            throw new ZeroImageDimensionException('Width and height of the image must be greater than zero.', 1597310560);
        }
        $result = ImageProcessingInstructions::fromCropScaleValues(
            $imageWidth,
            $imageHeight,
            $config['width'] ?? '',
            $config['height'] ?? '',
            $config
        );
        $imageWidth = $geometryWidth = $result->width;
        $imageHeight = $geometryHeight = $result->height;

        if ($result->useCropScaling) {
            $cropWidth = $result->originalWidth;
            $cropHeight = $result->originalHeight;
            // If the image is crop-scaled, use the dimension of the crop
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

        return new self($imageWidth, $imageHeight);
    }

    /**
     * @return array{
     *           width?: int<0, max>|string,
     *           height?: int<0, max>|string,
     *           maxWidth?: int<0, max>,
     *           maxHeight?: int<0, max>,
     *           maxW?: int<0, max>,
     *           maxH?: int<0, max>,
     *           minW?: int<0, max>,
     *           minH?: int<0, max>,
     *           crop?: Area,
     *           noScale?: bool
     *         }
     */
    private static function getConfigurationForImageCropScaleMask(TaskInterface $task): array
    {
        $configuration = $task->getConfiguration();

        if ($task->getTargetFile()->getTaskIdentifier() === ProcessedFile::CONTEXT_IMAGEPREVIEW) {
            // @todo: this ideally should not be necessary anymore with #102165
            if (method_exists($task, 'sanitizeConfiguration')) {
                $task->sanitizeConfiguration();
            }
            $configuration = $task->getConfiguration();
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
                    $options['crop'] = new Area((float)$cropData->x, (float)$cropData->y, (float)$cropData->width, (float)$cropData->height);
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
