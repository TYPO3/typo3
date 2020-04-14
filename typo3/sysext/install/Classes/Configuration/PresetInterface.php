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
 * Preset interface
 *
 * A preset is a class for handling a specific configuration
 * set of a feature.
 */
interface PresetInterface
{
    /**
     * Set POST values
     *
     * @param array $postValues Post values of feature
     * @return mixed
     */
    public function setPostValues(array $postValues);

    /**
     * Check if preset is available on the system
     *
     * @return bool TRUE if preset is available
     */
    public function isAvailable();

    /**
     * Wrapper for isAvailable, used in fluid
     *
     * @return bool TRUE if preset is available
     */
    public function getIsAvailable();

    /**
     * Check is preset is currently active on the system
     *
     * @return bool TRUE if preset is active
     */
    public function isActive();

    /**
     * Wrapper for isActive, used in fluid
     *
     * @return bool TRUE if preset is active
     */
    public function getIsActive();

    /**
     * Get name of preset
     *
     * @return string Name
     */
    public function getName();

    /**
     * Get priority of preset
     *
     * @return int Priority, usually between 0 and 100
     */
    public function getPriority();

    /**
     * Get configuration values to activate prefix
     *
     * @return array Configuration values needed to activate prefix
     */
    public function getConfigurationValues();
}
