<?php
namespace TYPO3\CMS\Install\Configuration\Image;

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

use TYPO3\CMS\Install\Configuration;

/**
 * Image feature detects imagemagick / graphicsmagick versions
 * @internal only to be used within EXT:install
 */
class ImageFeature extends Configuration\AbstractFeature implements Configuration\FeatureInterface
{
    /**
     * @var string Name of feature
     */
    protected $name = 'Image';

    /**
     * @var array List of preset classes
     */
    protected $presetRegistry = [
        \TYPO3\CMS\Install\Configuration\Image\GraphicsMagickPreset::class,
        \TYPO3\CMS\Install\Configuration\Image\ImageMagick6Preset::class,
        \TYPO3\CMS\Install\Configuration\Image\CustomPreset::class,
    ];

    /**
     * Image feature can be fed with an additional path to search for executables,
     * this getter returns the given input string (for Fluid)
     *
     * @return string
     */
    public function getAdditionalSearchPath()
    {
        $additionalPath = '';
        if (isset($this->postValues['additionalSearchPath']) && $this->postValues['additionalSearchPath'] !== '') {
            $additionalPath = $this->postValues['additionalSearchPath'];
        }
        return $additionalPath;
    }
}
