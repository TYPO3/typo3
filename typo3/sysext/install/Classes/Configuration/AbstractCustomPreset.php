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
 * Abstract custom preset class implements common preset code
 */
abstract class AbstractCustomPreset extends AbstractPreset
{
    /**
     * @var string Name of preset, always set to "Custom"
     */
    protected $name = 'Custom';

    /**
     * @var bool TRUE if custom preset is active
     */
    protected $isActive = false;

    /**
     * @var int Priority of custom prefix is usually the lowest
     */
    protected $priority = 10;

    /**
     * Whether custom preset is active is set by feature
     *
     * @return bool TRUE if custom preset is active
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Mark preset as active.
     * The custom features do not know by itself if they are
     * active or not since the configuration options may overlay
     * with other presets.
     * Marking the custom preset as active is therefor taken care
     * off by the feature itself if no other preset is active.
     */
    public function setActive()
    {
        $this->isActive = true;
    }

    /**
     * Custom configuration is always available
     *
     * @return bool TRUE
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * Get configuration values is used in fluid to show configuration options.
     * They are fetched from LocalConfiguration / DefaultConfiguration and
     * merged with given $postValues.
     *
     * @return array Configuration values needed to activate prefix
     */
    public function getConfigurationValues()
    {
        $configurationValues = [];
        foreach ($this->configurationValues as $configurationKey => $configurationValue) {
            if (isset($this->postValues['enable'])
                && $this->postValues['enable'] === $this->name
                && isset($this->postValues[$this->name][$configurationKey])
            ) {
                $currentValue = $this->postValues[$this->name][$configurationKey];
            } else {
                $currentValue = $this->configurationManager->getConfigurationValueByPath($configurationKey);
            }
            $configurationValues[$configurationKey] = $currentValue;
        }
        return $configurationValues;
    }
}
