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

namespace TYPO3\CMS\Install\Configuration;

/**
 * Custom preset interface
 *
 * Interface for presets not caught by other presets.
 * Represents "custom" configuration options of a feature.
 *
 * There must be only one custom preset per feature!
 */
interface CustomPresetInterface extends PresetInterface
{
    /**
     * Mark preset as active.
     * The custom features do not know by itself if they are
     * active or not since the configuration options may overlay
     * with other presets.
     * Marking the custom preset as active is therefor taken care
     * off by the feature itself if no other preset is active.
     */
    public function setActive();
}
