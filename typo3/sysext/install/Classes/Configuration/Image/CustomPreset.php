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
 * Custom preset is a fallback if no other preset fits
 */
class CustomPreset extends Configuration\AbstractCustomPreset implements Configuration\CustomPresetInterface
{
    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'GFX/processor_enabled' => false,
        'GFX/processor_path' => '',
        'GFX/processor_path_lzw' => '',
        'GFX/processor' => '',
        'GFX/processor_effects' => 0,
        'GFX/processor_allowTemporaryMasksAsPng' => true,
        'GFX/processor_colorspace' => '',
    ];
}
