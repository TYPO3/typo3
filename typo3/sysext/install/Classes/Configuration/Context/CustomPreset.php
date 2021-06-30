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

namespace TYPO3\CMS\Install\Configuration\Context;

use TYPO3\CMS\Install\Configuration\AbstractCustomPreset;
use TYPO3\CMS\Install\Configuration\CustomPresetInterface;

/**
 * Custom preset is a fallback if no other preset fits
 * @internal only to be used within EXT:install
 */
class CustomPreset extends AbstractCustomPreset implements CustomPresetInterface
{
    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'BE/debug' => '',
        'FE/debug' => '',
        'SYS/devIPmask' => '',
        'SYS/displayErrors' => '',
    ];
}
