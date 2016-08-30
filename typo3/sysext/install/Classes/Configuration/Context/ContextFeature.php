<?php
namespace TYPO3\CMS\Install\Configuration\Context;

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
 * Context feature sets development / production settings
 */
class ContextFeature extends Configuration\AbstractFeature implements Configuration\FeatureInterface
{
    /**
     * @var string Name of feature
     */
    protected $name = 'Context';

    /**
     * @var array List of preset classes
     */
    protected $presetRegistry = [
        \TYPO3\CMS\Install\Configuration\Context\LivePreset::class,
        \TYPO3\CMS\Install\Configuration\Context\DebugPreset::class,
        \TYPO3\CMS\Install\Configuration\Context\CustomPreset::class,
    ];
}
