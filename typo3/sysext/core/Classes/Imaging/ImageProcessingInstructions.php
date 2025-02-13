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
 * A DTO representing all information needed to process an image,
 * mainly the target dimensions.
 *
 * With this information an image can be processed by ImageMagick/GraphicsMagick.
 *
 * "cropScaling" refers to the logic where the image is cropped and scaled at the same time, which was
 * used back in TYPO3 v3/v4 but the "LocalCropScaleMaskHelper" is actually doing this in subsequent steps,
 * but should be merged together again once there is a load of more tests.
 *
 * @internal This object is still internal as long as cropping isn't migrated yet to the Crop API.
 */
readonly class ImageProcessingInstructions
{
    /**
     * @param int<0, max> $width
     * @param int<0, max> $height
     */
    public function __construct(
        public int $width = 0,
        public int $height = 0,
        public ?Area $cropArea = null,
    ) {}

    public static function fromProcessingTask(TaskInterface $task): ImageProcessingInstructions
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
        return ImageProcessingInstructions::fromCropScaleValues(
            $imageWidth,
            $imageHeight,
            $config['width'] ?? '',
            $config['height'] ?? '',
            $config
        );
    }

    /**
     * Get numbers for scaling the image based on input.
     *
     * Notes by Benni in 2023 in order to understand this magic:
     * ----------------------------
     * Relevant if an image should be
     * - scaled
     * - cropped
     * - keep the aspect ratio while scaling?
     * - use a target width or height
     * - or rather have a minimum or maximum width and/or height
     *
     * This method does a lot of magic:
     * - $incomingWidth/$incomingHeight contains the size of an original image for example.
     * - $w and $h are the width and height that are originally required the image to be like
     * when scaled. They could contain a "c" for cropping information or "m" for "Ensure that even though $w and $h are given, one containing an $m that we keep the aspect ratio."
     * "m" really allows to say $w="50c" that this might in a result with [0]=100 because $w would follow $h in order to keep aspect ratio.
     * Obviously this only works properly if both m and c are working
     * - $options contain "maxW" (never go beyond this width, even if scaling larger as this), same with "maxH" and "minW" and "minH" (note these get streamlined to maxWidth, maxHeight, minWidth, minHeight)
     *
     * The return values are a bit tricky to understand, so I added a few tests:
     * - AFAICS "0" and "1" are always used as "these are the target width / height" which my image
     *   should be scaled to, or cropped down to.
     *   Notes: If you hand in $info[0] and $incomingHeight a "0", you will get "0" as return value back!
     *          but
     * - "crs" if the image should be cropped (which is indicated by one of $w or $h contain the "c" at the end)
     * - "cropH" and "cropV" is also set when one of the incoming $w or $h contains a "c".
     *   Notes: "cropH" and "cropV" are rather cryptic, and can't really be used outside of this context.
     *          They are then "magically calculated" outside of this function
     *          $offsetX = (int)(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
     *          $offsetY = (int)(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200);
     *
     * - "origW" / "origH" seems to be the values that were handed in as $w and $h, but they might be altered
     *   f.e. "origH" is set when $w is given and $options["maxH"]
     * - When such a rearranging calculation was made ("maxH" reduces the original $w due to constraints),
     *   then the return value "max" is set.
     * - When using the "c" argument, origH and origW seem to contain the values that you would expect when NOT doing a crop scenario
     *   whereas $incomingWidth and $incomingHeight contain the target width and height that could be larger than originally requested.
     *
     * ----------------------------
     * @param int<0, max> $incomingWidth the width of an original image for example, can be "0" if there is no original image
     * @param int<0, max> $incomingHeight the height of an original image for example, can be "0" if there is no original image
     * @param int<0, max>|string $width "required" width that is requested, can be "" or "0" or a number of a magic "m" or "c" appended
     * @param int<0, max>|string $height "required" height that is requested, can be "" or "0" or a number of a magic "m" or "c" appended
     * @param array $options Options: Keys are like "maxW", "maxH", "minW", "minH" (streamlined to "maxWidth", "maxHeight", "minWidth", "minHeight")
     */
    public static function fromCropScaleValues(int $incomingWidth, int $incomingHeight, int|string $width, int|string $height, array $options): self
    {
        $options = self::streamlineOptions($options);

        if ($incomingWidth === 0 || $incomingHeight === 0) {
            // @todo incomingWidth/Height makes no sense, we should ideally throw an exception here…
            // this code is here to make existing unit tests happy and should be dropped
            return new self(
                width: 0,
                height: 0,
                cropArea: null
            );
        }

        $cropArea = ($options['crop'] ?? null) instanceof Area ? $options['crop'] : new Area(0, 0, $incomingWidth, $incomingHeight);

        // If both the width and the height are set and one of the numbers is appended by an m, the proportions will
        // be preserved and thus width and height are treated as maximum dimensions for the image. The image will be
        // scaled to fit into the rectangle of the dimensions width and height.
        $useWidthOrHeightAsMaximumLimits = str_contains($width . $height, 'm');
        $useCropScaling = str_contains($width . $height, 'c');

        if ($useWidthOrHeightAsMaximumLimits && $useCropScaling) {
            throw new \InvalidArgumentException('Cannot mix m and c modifiers for width/height', 1709840402);
        }

        if ($useWidthOrHeightAsMaximumLimits) {
            if (str_contains((string)$width, 'm')) {
                $options['maxWidth'] = min((int)$width, (int)($options['maxWidth'] ?? PHP_INT_MAX));
                // width: auto
                $width = 0;
            }
            if (str_contains((string)$height, 'm')) {
                $options['maxHeight'] = min((int)$height, (int)($options['maxHeight'] ?? PHP_INT_MAX));
                // height: auto
                $height = 0;
            }
        }

        if ((int)$width !== 0 && (int)$height !== 0 && $useCropScaling) {
            $cropOffsetHorizontal = (int)substr((string)strstr((string)$width, 'c'), 1);
            $cropOffsetVertical = (int)substr((string)strstr((string)$height, 'c'), 1);
            $width = (int)$width;
            $height = (int)$height;

            $cropArea = self::applyCropScaleToCropArea($cropArea, $width, $height, $cropOffsetVertical, $cropOffsetHorizontal);
        }

        $width = (int)$width;
        $height = (int)$height;

        // Rounding in extreme formats like 1920x10 to 64x??? can yield a 0 height/width, which should be at least 1 pixel.
        // Because of this, the following checks use a max(1, $maybeZero) assignment.
        if ($width > 0 && $height === 0) {
            $height = max(1, (int)round($cropArea->getHeight() * ($width / $cropArea->getWidth())));
        }
        if ($height > 0 && $width === 0) {
            $width = max(1, (int)round($cropArea->getWidth() * ($height / $cropArea->getHeight())));
        }

        // If there are max/min-values...
        if (!empty($options['maxWidth'])) {
            if ($width > $options['maxWidth'] || ($width === 0 && $cropArea->getWidth() > $options['maxWidth'])) {
                $width = (int)$options['maxWidth'];
                $height = max(1, (int)round($cropArea->getHeight() * ($width / $cropArea->getWidth())));
            }
        }
        if (!empty($options['maxHeight'])) {
            if ($height > $options['maxHeight'] || ($height === 0 && $cropArea->getHeight() > $options['maxHeight'])) {
                $height = (int)$options['maxHeight'];
                $width = max(1, (int)round($cropArea->getWidth() * ($height / $cropArea->getHeight())));
            }
        }

        if (!empty($options['minWidth'])) {
            if ($width < $options['minWidth'] || ($width === 0 && $cropArea->getWidth() < $options['minWidth'])) {
                $width = (int)$options['minWidth'];
                $height = max(1, (int)round($cropArea->getHeight() * ($width / $cropArea->getWidth())));
            }
        }
        if (!empty($options['minHeight'])) {
            if ($height < $options['minHeight'] || ($height === 0 && $cropArea->getHeight() < $options['minHeight'])) {
                $height = $options['minHeight'];
                $width = max(1, (int)round($cropArea->getWidth() * ($height / $cropArea->getHeight())));
            }
        }

        if ($width === 0 && $height === 0) {
            $width = (int)round($cropArea->getWidth());
            $height = (int)round($cropArea->getHeight());
            // This here may return "0", which should continue to throw a LogicException. Probably.
        }
        if ($width === 0 || $height === 0) {
            throw new \LogicException('Image processing instructions did not resolve into coherent positive width and height values. This is a bug. Please report.', 1709806820);
        }

        if (!($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] ?? false)) {
            if ($width > $cropArea->getWidth()) {
                $width = (int)round($cropArea->getWidth());
                $height = (int)round($cropArea->getHeight() * ($width / $cropArea->getWidth()));
            }
            if ($height > $cropArea->getHeight()) {
                $height = (int)round($cropArea->getHeight());
                $width = (int)round($cropArea->getWidth() * ($height / $cropArea->getHeight()));
            }
        }

        if ((int)$cropArea->getOffsetLeft() === 0 &&
            (int)$cropArea->getOffsetTop() === 0 &&
            (int)$cropArea->getWidth() === $incomingWidth &&
            (int)$cropArea->getHeight() === $incomingHeight) {
            $cropArea = null;
        }

        return new self(
            width: $width,
            height: $height,
            cropArea: $cropArea,
        );
    }

    /**
     * @param Area $cropArea with absolute crop data (not relative!)
     * @param positive-int $width
     * @param positive-int $height
     * @param int<-100,100> $cropOffsetVertical
     * @param int<-100,100> $cropOffsetHorizontal
     */
    private static function applyCropScaleToCropArea(
        Area $cropArea,
        int $width,
        int $height,
        int $cropOffsetVertical,
        int $cropOffsetHorizontal
    ): Area {
        // @phpstan-ignore-next-line
        if (!($width > 0 && $height > 0 && $cropArea->getWidth() > 0 && $cropArea->getHeight() > 0)) {
            throw new \InvalidArgumentException('Apply crop scale must use concrete width and height', 1709810881);
        }
        $destRatio = $width / $height;
        $cropRatio = $cropArea->getWidth() / $cropArea->getHeight();

        if ($destRatio > $cropRatio) {
            $w = $cropArea->getWidth();
            $h = $cropArea->getWidth() / $destRatio;
            $x = $cropArea->getOffsetLeft();
            $y = $cropArea->getOffsetTop() + (float)(($cropArea->getHeight() - $h) * ($cropOffsetVertical + 100) / 200);
        } else {
            $w = $cropArea->getHeight() * $destRatio;
            $h = $cropArea->getHeight();
            $x = $cropArea->getOffsetLeft() + (float)(($cropArea->getWidth() - $w) * ($cropOffsetHorizontal + 100) / 200);
            $y = $cropArea->getOffsetTop();
        }

        return new Area($x, $y, $w, $h);
    }

    /**
     * @return array{
     *             maxWidth?: int,
     *             maxHeight?: int,
     *             minWidth?: int,
     *             minHeight?: int,
     *             crop?: Area,
     *         }
     */
    private static function streamlineOptions(array $options): array
    {
        if (isset($options['maxW'])) {
            $options['maxWidth'] = $options['maxW'];
            unset($options['maxW']);
        }
        if (isset($options['maxH'])) {
            $options['maxHeight'] = $options['maxH'];
            unset($options['maxH']);
        }
        if (isset($options['minW'])) {
            $options['minWidth'] = $options['minW'];
            unset($options['minW']);
        }
        if (isset($options['minH'])) {
            $options['minHeight'] = $options['minH'];
            unset($options['minH']);
        }

        if (($options['maxWidth'] ?? null) <= 0) {
            unset($options['maxWidth']);
        }
        if (($options['maxHeight'] ?? null) <= 0) {
            unset($options['maxHeight']);
        }
        if (($options['minWidth'] ?? null) <= 0) {
            unset($options['minWidth']);
        }
        if (($options['minHeight'] ?? null) <= 0) {
            unset($options['minHeight']);
        }

        if (isset($options['crop'])) {
            if (is_string($options['crop'])) {
                // check if it is a json object
                $cropData = json_decode($options['crop']);
                if ($cropData) {
                    // happens when $options['crop'] = '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}'
                    if (!isset($cropData->x) || !isset($cropData->y) || !isset($cropData->width) || !isset($cropData->height)) {
                        unset($options['crop']);
                    } else {
                        $options['crop'] = new Area((float)$cropData->x, (float)$cropData->y, (float)$cropData->width, (float)$cropData->height);
                    }
                } else {
                    [$offsetLeft, $offsetTop, $newWidth, $newHeight] = explode(',', $options['crop'], 4);
                    $options['crop'] = new Area((float)$offsetLeft, (float)$offsetTop, (float)$newWidth, (float)$newHeight);
                }
                if (isset($options['crop']) && $options['crop']->isEmpty()) {
                    unset($options['crop']);
                }
            } elseif (!$options['crop'] instanceof Area) {
                unset($options['crop']);
            }
        }
        return $options;
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
            $task->sanitizeConfiguration();
            // @todo: this transformation needs to happen in the PreviewTask, but if we do this,
            // all preview images would be re-created, so we should be careful when to do this.
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
