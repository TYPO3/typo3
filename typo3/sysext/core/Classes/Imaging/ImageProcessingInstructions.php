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
class ImageProcessingInstructions
{
    /** @var int<0, max> */
    public int $originalWidth = 0;
    /** @var int<0, max> */
    public int $originalHeight = 0;
    /** @var int<0, max> */
    public int $width = 0;
    /** @var int<0, max> */
    public int $height = 0;
    public bool $useCropScaling = false;
    public ?Area $cropArea = null;
    public array $options = [];

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
     * - $options contain "maxW" (never go beyond this width, even if scaling larger as this), same with "maxH" and "minW" and "minH"
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
     * @param int<0, max> $incomingWidth the width of an original image for example, can be "0" if there is no original image (thus, it will remain "0" in the "originalWidth")
     * @param int<0, max> $incomingHeight the height of an original image for example, can be "0" if there is no original image (thus, it will remain "0" in the "origHeight")
     * @param int<0, max>|string $width "required" width that is requested, can be "" or "0" or a number of a magic "m" or "c" appended
     * @param int<0, max>|string $height "required" height that is requested, can be "" or "0" or a number of a magic "m" or "c" appended
     * @param array $options Options: Keys are like "maxW", "maxH", "minW", "minH"
     */
    public static function fromCropScaleValues(int $incomingWidth, int $incomingHeight, int|string $width, int|string $height, array $options): self
    {
        $cropOffsetHorizontal = 0;
        $cropOffsetVertical = 0;
        $options = self::streamlineOptions($options);
        $obj = new self();
        // If both the width and the height are set and one of the numbers is appended by an m, the proportions will
        // be preserved and thus width and height are treated as maximum dimensions for the image. The image will be
        // scaled to fit into the rectangle of the dimensions width and height.
        $useWidthOrHeightAsMaximumLimits = str_contains($width . $height, 'm');
        if (($options['crop'] ?? null) instanceof Area) {
            $obj->cropArea = $options['crop'];
            unset($options['crop']);
        } elseif (str_contains($width . $height, 'c')) {
            $cropOffsetHorizontal = (int)substr((string)strstr((string)$width, 'c'), 1);
            $cropOffsetVertical = (int)substr((string)strstr((string)$height, 'c'), 1);
            $obj->useCropScaling = true;
        }
        $width = (int)$width;
        $height = (int)$height;
        // If there are max-values...
        if (!empty($options['maxWidth'])) {
            // If width is given...
            if ($width > 0) {
                if ($width > $options['maxWidth']) {
                    $width = $options['maxWidth'];
                    // Height should follow
                    $useWidthOrHeightAsMaximumLimits = true;
                }
            } else {
                if ($incomingWidth > $options['maxWidth']) {
                    $width = $options['maxWidth'];
                    // Height should follow
                    $useWidthOrHeightAsMaximumLimits = true;
                }
            }
        }
        if (!empty($options['maxHeight'])) {
            // If height is given...
            if ($height > 0) {
                if ($height > $options['maxHeight']) {
                    $height = $options['maxHeight'];
                    // Height should follow
                    $useWidthOrHeightAsMaximumLimits = true;
                }
            } else {
                // Changed [0] to [1] 290801
                if ($incomingHeight > $options['maxHeight']) {
                    $height = $options['maxHeight'];
                    // Height should follow
                    $useWidthOrHeightAsMaximumLimits = true;
                }
            }
        }
        $obj->originalWidth = $width;
        $obj->originalHeight = $height;
        if (!($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] ?? false)) {
            if ($width > $incomingWidth) {
                $width = $incomingWidth;
            }
            if ($height > $incomingHeight) {
                $height = $incomingHeight;
            }
        }
        // If scaling should be performed. Check that input "info" array will not cause division-by-zero
        if (($width > 0 || $height > 0) && $incomingWidth && $incomingHeight) {
            if ($width > 0 && $height === 0) {
                $incomingHeight = (int)ceil($incomingHeight * ($width / $incomingWidth));
                $incomingWidth = $width;
            }
            if ((int)$width === 0 && $height > 0) {
                $incomingWidth = (int)ceil($incomingWidth * ($height / $incomingHeight));
                $incomingHeight = $height;
            }
            if ($width !== 0 && $height !== 0) {
                if ($useWidthOrHeightAsMaximumLimits) {
                    $ratio = $incomingWidth / $incomingHeight;
                    if ($height * $ratio > $width) {
                        $height = (int)round($width / $ratio);
                    } else {
                        $width = (int)round($height * $ratio);
                    }
                }
                if ($obj->useCropScaling) {
                    $ratio = $incomingWidth / $incomingHeight;
                    if ($height * $ratio < $width) {
                        $height = (int)round($width / $ratio);
                    } else {
                        $width = (int)round($height * $ratio);
                    }
                }
                $incomingWidth = $width;
                $incomingHeight = $height;
            }
        }
        $resultWidth = $incomingWidth;
        $resultHeight = $incomingHeight;
        // Set minimum-measures!
        if (isset($options['minWidth']) && $resultWidth < $options['minWidth']) {
            if (($useWidthOrHeightAsMaximumLimits || $obj->useCropScaling) && $resultWidth) {
                $resultHeight = (int)round($resultHeight * $options['minWidth'] / $resultWidth);
            }
            $resultWidth = $options['minWidth'];
        }
        if (isset($options['minHeight']) && $resultHeight < $options['minHeight']) {
            if (($useWidthOrHeightAsMaximumLimits || $obj->useCropScaling) && $resultHeight) {
                $resultWidth = (int)round($resultWidth * $options['minHeight'] / $resultHeight);
            }
            $resultHeight = $options['minHeight'];
        }
        $obj->width = $resultWidth;
        $obj->height = $resultHeight;

        // The incoming values are percentage values, and need to be calculated in
        // the actual width and height of the target file size, see https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Imgresource.html#width
        // This needs a special calculation "magic", instead of using the "cropArea" feature.
        // which TYPO3 uses since v8 which ships with a "cropArea" object right away.
        if ($obj->useCropScaling && !$obj->cropArea) {
            $cropWidth = $obj->originalWidth ?: $obj->width;
            $cropHeight = $obj->originalHeight ?: $obj->height;
            $offsetX = (float)(($obj->width - $obj->originalWidth) * ($cropOffsetHorizontal + 100) / 200);
            $offsetY = (float)(($obj->height - $obj->originalHeight) * ($cropOffsetVertical + 100) / 200);

            $obj->cropArea = new Area(
                $offsetX,
                $offsetY,
                (float)$cropWidth,
                (float)$cropHeight,
            );
        }

        return $obj;
    }

    public static function streamlineOptions(array $options): array
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
        if (isset($options['crop'])) {
            if (is_string($options['crop'])) {
                // check if it is a json object
                $cropData = json_decode($options['crop']);
                if ($cropData) {
                    $options['crop'] = new Area((float)$cropData->x, (float)$cropData->y, (float)$cropData->width, (float)$cropData->height);
                } else {
                    [$offsetLeft, $offsetTop, $newWidth, $newHeight] = explode(',', $options['crop'], 4);
                    $options['crop'] = new Area((float)$offsetLeft, (float)$offsetTop, (float)$newWidth, (float)$newHeight);
                }
                if ($options['crop']->isEmpty()) {
                    unset($options['crop']);
                }
            } elseif (!$options['crop'] instanceof Area) {
                unset($options['crop']);
            }
        }
        return $options;
    }
}
