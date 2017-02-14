<?php
namespace TYPO3\CMS\Install\Configuration;

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

/**
 * A feature representation handles preset classes.
 */
interface FeatureInterface
{
    /**
     * Initialize presets
     *
     * @param array $postValues List of $POST values of this feature
     */
    public function initializePresets(array $postValues);

    /**
     * Get list of presets ordered by priority
     *
     * @return array<PresetInterface>
     */
    public function getPresetsOrderedByPriority();

    /**
     * Get name of feature
     *
     * @return string Name
     */
    public function getName();
}
