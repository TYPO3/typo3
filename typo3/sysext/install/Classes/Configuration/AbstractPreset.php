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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract preset class implements common preset code
 * @internal only to be used within EXT:install
 */
abstract class AbstractPreset implements PresetInterface
{
    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string Name of preset, must be set by extending classes
     */
    protected $name = '';

    /**
     * @var int Default priority of preset
     */
    protected $priority = 50;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [];

    /**
     * @var array List of $POST values
     */
    protected $postValues = [];

    /**
     * @param \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager = null)
    {
        $this->configurationManager = $configurationManager ?: GeneralUtility::makeInstance(ConfigurationManager::class);
    }

    /**
     * Set POST values
     *
     * @param array $postValues Post values of feature
     * @return mixed
     */
    public function setPostValues(array $postValues)
    {
        $this->postValues = $postValues;
    }

    /**
     * Wrapper for isAvailable, used in fluid
     *
     * @return bool TRUE if preset is available
     */
    public function getIsAvailable()
    {
        return $this->isAvailable();
    }

    /**
     * Check is preset is currently active on the system
     *
     * @return bool TRUE if preset is active
     */
    public function isActive()
    {
        $isActive = true;
        foreach ($this->configurationValues as $configurationKey => $configurationValue) {
            try {
                $currentValue = $this->configurationManager->getConfigurationValueByPath($configurationKey);
            } catch (MissingArrayPathException $e) {
                $currentValue = null;
            }
            if ($currentValue !== $configurationValue) {
                $isActive = false;
                break;
            }
        }
        return $isActive;
    }

    /**
     * Wrapper for isActive, used in fluid
     *
     * @return bool TRUE if preset is active
     */
    public function getIsActive()
    {
        return $this->isActive();
    }

    /**
     * Get name of preset
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get priority of preset
     *
     * @return int Priority, usually between 0 and 100
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Get configuration values to activate prefix
     *
     * @return array Configuration values needed to activate prefix
     */
    public function getConfigurationValues()
    {
        return $this->configurationValues;
    }
}
