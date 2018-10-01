<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Configuration\PasswordHashing;

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

use TYPO3\CMS\Install\Configuration\AbstractCustomPreset;
use TYPO3\CMS\Install\Configuration\CustomPresetInterface;

/**
 * Preset used if custom password hashing configuration has been applied.
 * Note this custom preset does not allow manipulation via gui, this has to be done manually.
 * This preset only find out if it is active and shows the current values.
 * @internal only to be used within EXT:install
 */
class CustomPreset extends AbstractCustomPreset implements CustomPresetInterface
{
    /**
     * Get configuration values is used in fluid to show configuration options.
     * They are fetched from LocalConfiguration / DefaultConfiguration.
     *
     * @return array Current custom configuration values
     */
    public function getConfigurationValues(): array
    {
        $configurationValues = [];
        $configurationValues['BE/passwordHashing/className'] =
            $this->configurationManager->getConfigurationValueByPath('BE/passwordHashing/className');
        $options = (array)$this->configurationManager->getConfigurationValueByPath('BE/passwordHashing/options');
        foreach ($options as $optionName => $optionValue) {
            $configurationValues['BE/passwordHashing/options/' . $optionName] = $optionValue;
        }
        $configurationValues['FE/passwordHashing/className'] =
            $this->configurationManager->getConfigurationValueByPath('FE/passwordHashing/className');
        $options = (array)$this->configurationManager->getConfigurationValueByPath('FE/passwordHashing/options');
        foreach ($options as $optionName => $optionValue) {
            $configurationValues['FE/passwordHashing/options/' . $optionName] = $optionValue;
        }
        return $configurationValues;
    }
}
