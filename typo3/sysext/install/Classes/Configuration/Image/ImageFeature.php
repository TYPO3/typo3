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

namespace TYPO3\CMS\Install\Configuration\Image;

use TYPO3\CMS\Install\Configuration\AbstractFeature;
use TYPO3\CMS\Install\Configuration\FeatureInterface;

/**
 * Image feature detects imagemagick / graphicsmagick versions
 * @internal only to be used within EXT:install
 */
class ImageFeature extends AbstractFeature implements FeatureInterface
{
    /**
     * @var string Name of feature
     */
    protected $name = 'Image';

    /**
     * @var array List of preset classes
     */
    protected $presetRegistry = [
        GraphicsMagickPreset::class,
        ImageMagick6Preset::class,
        CustomPreset::class,
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
