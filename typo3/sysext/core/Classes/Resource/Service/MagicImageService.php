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

namespace TYPO3\CMS\Core\Resource\Service;

use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Magic image service
 */
class MagicImageService
{
    /**
     * Maximum width of magic images
     * These defaults allow images to be based on their width - to a certain degree - by setting a high height.
     * Then we're almost certain the image will be based on the width
     * @var int
     */
    protected $magicImageMaximumWidth = 300;

    /**
     * Maximum height of magic images
     * @var int
     */
    protected $magicImageMaximumHeight = 1000;

    /**
     * Creates a magic image
     *
     * @param Resource\File $imageFileObject the original image file
     * @param array $fileConfiguration (width, height)
     * @return Resource\ProcessedFile
     */
    public function createMagicImage(File $imageFileObject, array $fileConfiguration)
    {
        // Process dimensions
        $maxWidth = MathUtility::forceIntegerInRange($fileConfiguration['width'] ?? 0, 0, $this->magicImageMaximumWidth);
        $maxHeight = MathUtility::forceIntegerInRange($fileConfiguration['height'] ?? 0, 0, $this->magicImageMaximumHeight);
        if (!$maxWidth) {
            $maxWidth = $this->magicImageMaximumWidth;
        }
        if (!$maxHeight) {
            $maxHeight = $this->magicImageMaximumHeight;
        }
        // Create the magic image
        $magicImage = $imageFileObject->process(
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            [
                'width' => $maxWidth . 'm',
                'height' => $maxHeight . 'm',
            ]
        );
        return $magicImage;
    }

    /**
     * Set maximum dimensions of magic images based on RTE configuration
     *
     * @param array $rteConfiguration RTE configuration probably coming from PageTSConfig
     */
    public function setMagicImageMaximumDimensions(array $rteConfiguration)
    {
        $imageButtonConfiguration = [];
        // Get maximum dimensions from the configuration of the RTE image button
        if (is_array($rteConfiguration['buttons.']['image.'] ?? null)) {
            $imageButtonConfiguration = $rteConfiguration['buttons.']['image.'];
        }
        if (is_array($imageButtonConfiguration['options.']['magic.'] ?? null)) {
            if ((int)($imageButtonConfiguration['options.']['magic.']['maxWidth'] ?? 0) > 0) {
                $this->magicImageMaximumWidth = (int)$imageButtonConfiguration['options.']['magic.']['maxWidth'];
            }
            if ((int)($imageButtonConfiguration['options.']['magic.']['maxHeight'] ?? 0) > 0) {
                $this->magicImageMaximumHeight = (int)$imageButtonConfiguration['options.']['magic.']['maxHeight'];
            }
        }
    }
}
