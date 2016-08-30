<?php
namespace TYPO3\CMS\Install\Configuration\ExtbaseObjectCache;

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
 * Extbase object cache configuration
 */
class ExtbaseObjectCacheFeature extends Configuration\AbstractFeature implements Configuration\FeatureInterface
{
    /**
     * @var string Name of feature
     */
    protected $name = 'ExtbaseObjectCache';

    /**
     * @var array List of preset classes
     */
    protected $presetRegistry = [
        DatabasePreset::class,
        ApcPreset::class,
        ApcuPreset::class,
    ];
}
