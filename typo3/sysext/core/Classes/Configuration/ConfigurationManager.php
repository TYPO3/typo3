<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Configuration;

use TYPO3\CMS\Core\Configuration\Exception\SettingsWriteException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle loading and writing of global and local (instance specific)
 * configuration.
 *
 * This class handles the access to the files
 * - EXT:core/Configuration/DefaultConfiguration.php (default TYPO3_CONF_VARS)
 * - config/system/settings.php or typo3conf/system/settings.php - previously known as LocalConfiguration.php
 * - config/system/additional.php or typo3conf/system/additional.php (optional additional code blocks) - previously known as typo3conf/AdditionalConfiguration.php
 *
 * IMPORTANT:
 *   This class is intended for internal core use ONLY.
 *   Extensions should usually use the resulting $GLOBALS['TYPO3_CONF_VARS'] array,
 *   do not try to modify settings in the config/system/settings.php file with an extension.
 * @internal
 */
class ConfigurationManager
{
    /**
     * @var string Path to default TYPO3_CONF_VARS file, relative to the public web folder
     */
    protected $defaultConfigurationFile = __DIR__ . '/../../Configuration/DefaultConfiguration.php';

    /**
     * @var string Path to description file for TYPO3_CONF_VARS, relative to the public web folder
     */
    protected $defaultConfigurationDescriptionFile = 'EXT:core/Configuration/DefaultConfigurationDescription.yaml';

    /**
     * @var string Path to factory configuration file used during installation as LocalConfiguration boilerplate
     */
    protected $factoryConfigurationFile = __DIR__ . '/../../Configuration/FactoryConfiguration.php';

    /**
     * @var string Path to possible additional factory configuration file delivered by packages
     */
    protected $additionalFactoryConfigurationFile = 'AdditionalFactoryConfiguration.php';

    /**
     * Writing to these configuration paths is always allowed,
     * even if the requested sub path does not exist yet.
     */
    protected array $allowedSettingsPaths = [
        'EXTCONF',
        'DB',
        'SYS/caching/cacheConfigurations',
        'SYS/encryptionKey',
        'SYS/session',
        'EXTENSIONS',
    ];

    /**
     * Return default configuration array
     *
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return require $this->getDefaultConfigurationFileLocation();
    }

    /**
     * Get the file location of the default configuration file,
     * currently the path and filename.
     *
     * @return string
     * @internal
     */
    public function getDefaultConfigurationFileLocation()
    {
        return $this->defaultConfigurationFile;
    }

    /**
     * Get the file location of the default configuration description file,
     * currently the path and filename.
     *
     * @return string
     * @internal
     */
    public function getDefaultConfigurationDescriptionFileLocation()
    {
        return $this->defaultConfigurationDescriptionFile;
    }

    /**
     * Return configuration array of typo3conf/system/settings.php or config/system/settings.php, falls back
     * to typo3conf/LocalConfiguration.php
     *
     * @return array Content array of local configuration file
     */
    public function getLocalConfiguration(): array
    {
        $settingsFile = $this->getSystemConfigurationFileLocation();
        if (is_file($settingsFile)) {
            return require $settingsFile;
        }
        return require $this->getLocalConfigurationFileLocation();
    }

    /**
     * Get the file location of the local configuration file,
     * currently the path and filename.
     *
     * Path to local overload TYPO3_CONF_VARS file.
     *
     * @internal
     */
    public function getLocalConfigurationFileLocation(): string
    {
        return Environment::getLegacyConfigPath() . '/LocalConfiguration.php';
    }

    /**
     * Get the file location of the TYPO3-project specific settings file,
     * currently the path and filename.
     *
     * Path to local overload TYPO3_CONF_VARS file.
     *
     * @internal
     */
    public function getSystemConfigurationFileLocation(bool $relativeToProjectRoot = false): string
    {
        // For composer-based installations, the file is in config/system/settings.php
        if (Environment::getProjectPath() !== Environment::getPublicPath()) {
            $path = Environment::getConfigPath() . '/system/settings.php';
        } else {
            $path = Environment::getLegacyConfigPath() . '/system/settings.php';
        }
        if ($relativeToProjectRoot) {
            return substr($path, strlen(Environment::getProjectPath()) + 1);
        }
        return $path;
    }

    /**
     * Returns local configuration array merged with default configuration
     */
    public function getMergedLocalConfiguration(): array
    {
        $localConfiguration = $this->getDefaultConfiguration();
        ArrayUtility::mergeRecursiveWithOverrule($localConfiguration, $this->getLocalConfiguration());
        return $localConfiguration;
    }

    /**
     * Get the file location of the additional configuration file,
     * currently the path and filename.
     *
     * @return string
     * @internal
     */
    public function getAdditionalConfigurationFileLocation()
    {
        // For composer-based installations, the file is in config/system/additional.php
        if (Environment::getProjectPath() !== Environment::getPublicPath()) {
            return Environment::getConfigPath() . '/system/additional.php';
        }
        return Environment::getLegacyConfigPath() . '/system/additional.php';
    }

    /**
     * Get absolute file location of factory configuration file
     *
     * @return string
     */
    protected function getFactoryConfigurationFileLocation()
    {
        return $this->factoryConfigurationFile;
    }

    /**
     * Get absolute file location of factory configuration file
     *
     * @return string
     */
    protected function getAdditionalFactoryConfigurationFileLocation()
    {
        return Environment::getLegacyConfigPath() . '/' . $this->additionalFactoryConfigurationFile;
    }

    /**
     * Override local configuration with new values.
     *
     * @param array $configurationToMerge Override configuration array
     */
    public function updateLocalConfiguration(array $configurationToMerge)
    {
        $newLocalConfiguration = $this->getLocalConfiguration();
        ArrayUtility::mergeRecursiveWithOverrule($newLocalConfiguration, $configurationToMerge);
        $this->writeLocalConfiguration($newLocalConfiguration);
    }

    /**
     * Get a value at given path from default configuration
     *
     * @param string $path Path to search for
     * @return mixed Value at path
     */
    public function getDefaultConfigurationValueByPath($path)
    {
        return ArrayUtility::getValueByPath($this->getDefaultConfiguration(), $path);
    }

    /**
     * Get a value at given path from local configuration
     *
     * @param string $path Path to search for
     * @return mixed Value at path
     */
    public function getLocalConfigurationValueByPath($path)
    {
        return ArrayUtility::getValueByPath($this->getLocalConfiguration(), $path);
    }

    /**
     * Get a value from configuration, this is default configuration
     * merged with local configuration
     *
     * @param string $path Path to search for
     * @return mixed
     */
    public function getConfigurationValueByPath($path)
    {
        $defaultConfiguration = $this->getDefaultConfiguration();
        ArrayUtility::mergeRecursiveWithOverrule($defaultConfiguration, $this->getLocalConfiguration());
        return ArrayUtility::getValueByPath($defaultConfiguration, $path);
    }

    /**
     * Update a given path in local configuration to a new value.
     * Warning: TO BE USED ONLY to update a single feature.
     * NOT TO BE USED within iterations to update multiple features.
     * To update multiple features use setLocalConfigurationValuesByPathValuePairs().
     *
     * @param string $path Path to update
     * @param mixed $value Value to set
     * @return bool TRUE on success
     */
    public function setLocalConfigurationValueByPath($path, $value)
    {
        $result = false;
        if ($this->isValidLocalConfigurationPath($path)) {
            $localConfiguration = $this->getLocalConfiguration();
            $localConfiguration = ArrayUtility::setValueByPath($localConfiguration, $path, $value);
            $result = $this->writeLocalConfiguration($localConfiguration);
        }
        return $result;
    }

    /**
     * Update / set a list of path and value pairs in local configuration file
     *
     * @param array $pairs Key is path, value is value to set
     * @return bool TRUE on success
     */
    public function setLocalConfigurationValuesByPathValuePairs(array $pairs)
    {
        $localConfiguration = $this->getLocalConfiguration();
        foreach ($pairs as $path => $value) {
            if ($this->isValidLocalConfigurationPath($path)) {
                $localConfiguration = ArrayUtility::setValueByPath($localConfiguration, $path, $value);
            }
        }
        return $this->writeLocalConfiguration($localConfiguration);
    }

    /**
     * Remove keys from LocalConfiguration
     *
     * @param array $keys Array with key paths to remove from LocalConfiguration
     * @return bool TRUE if something was removed
     */
    public function removeLocalConfigurationKeysByPath(array $keys): bool
    {
        $result = false;
        $localConfiguration = $this->getLocalConfiguration();
        foreach ($keys as $path) {
            // Remove key if path is within LocalConfiguration
            if (ArrayUtility::isValidPath($localConfiguration, $path)) {
                $result = true;
                $localConfiguration = ArrayUtility::removeByPath($localConfiguration, $path);
            }
        }
        if ($result) {
            $this->writeLocalConfiguration($localConfiguration);
        }
        return $result;
    }

    /**
     * Enables a certain feature and writes the option to system/settings.php
     * Short-hand method
     * Warning: TO BE USED ONLY to enable a single feature.
     * NOT TO BE USED within iterations to enable multiple features.
     * To update multiple features use setLocalConfigurationValuesByPathValuePairs().
     *
     * @param string $featureName something like "InlineSvgImages"
     * @return bool true on successful writing the setting
     */
    public function enableFeature(string $featureName): bool
    {
        return $this->setLocalConfigurationValueByPath('SYS/features/' . $featureName, true);
    }

    /**
     * Disables a feature and writes the option to system/settings.php
     * Short-hand method
     * Warning: TO BE USED ONLY to disable a single feature.
     * NOT TO BE USED within iterations to disable multiple features.
     * To update multiple features use setLocalConfigurationValuesByPathValuePairs().
     *
     * @param string $featureName something like "InlineSvgImages"
     * @return bool true on successful writing the setting
     */
    public function disableFeature(string $featureName): bool
    {
        return $this->setLocalConfigurationValueByPath('SYS/features/' . $featureName, false);
    }

    /**
     * Checks if the configuration can be written.
     *
     * @return bool
     * @internal
     */
    public function canWriteConfiguration()
    {
        $fileLocation = $this->getSystemConfigurationFileLocation();
        return @is_writable(file_exists($fileLocation) ? $fileLocation : dirname($fileLocation));
    }

    /**
     * Reads the configuration array and exports it to the global variable
     *
     * @internal
     * @throws \UnexpectedValueException
     */
    public function exportConfiguration(): void
    {
        if (@is_file($this->getSystemConfigurationFileLocation())) {
            $localConfiguration = $this->getLocalConfiguration();
            $defaultConfiguration = $this->getDefaultConfiguration();
            ArrayUtility::mergeRecursiveWithOverrule($defaultConfiguration, $localConfiguration);
            $GLOBALS['TYPO3_CONF_VARS'] = $defaultConfiguration;
        } else {
            // No LocalConfiguration (yet), load DefaultConfiguration
            $GLOBALS['TYPO3_CONF_VARS'] = $this->getDefaultConfiguration();
        }

        // Load AdditionalConfiguration
        if (@is_file($this->getAdditionalConfigurationFileLocation())) {
            require $this->getAdditionalConfigurationFileLocation();
        }
    }

    /**
     * Write configuration array to %config-dir%/system/settings.php
     *
     * @param array $configuration The local configuration to be written
     * @throws \RuntimeException
     * @return bool TRUE on success
     * @internal
     */
    public function writeLocalConfiguration(array $configuration)
    {
        $systemSettingsFile = $this->getSystemConfigurationFileLocation();
        if (!$this->canWriteConfiguration()) {
            throw new SettingsWriteException(
                $this->getSystemConfigurationFileLocation(true) . ' is not writable.',
                1346323822
            );
        }
        $configuration = ArrayUtility::sortByKeyRecursive($configuration);
        $result = GeneralUtility::writeFile(
            $systemSettingsFile,
            "<?php\n" .
                'return ' .
                    ArrayUtility::arrayExport($configuration) .
                ";\n",
            true
        );

        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($systemSettingsFile);

        return $result;
    }

    /**
     * Write additional configuration array to config/system/additional.php / typo3conf/system/additional.php
     *
     * @param array $additionalConfigurationLines The configuration lines to be written
     * @throws \RuntimeException
     * @return bool TRUE on success
     * @internal
     */
    public function writeAdditionalConfiguration(array $additionalConfigurationLines)
    {
        return GeneralUtility::writeFile(
            $this->getAdditionalConfigurationFileLocation(),
            "<?php\n" . implode("\n", $additionalConfigurationLines) . "\n",
            true
        );
    }

    /**
     * Uses FactoryConfiguration file and a possible AdditionalFactoryConfiguration
     * file in typo3conf to create a basic config/system/settings.php. This is used
     * by the installer in an early step.
     *
     * @throws \RuntimeException
     * @internal
     */
    public function createLocalConfigurationFromFactoryConfiguration()
    {
        if (file_exists($this->getSystemConfigurationFileLocation())) {
            throw new \RuntimeException(
                basename($this->getSystemConfigurationFileLocation(true)) . ' already exists',
                1364836026
            );
        }
        $localConfigurationArray = require $this->getFactoryConfigurationFileLocation();
        $additionalFactoryConfigurationFileLocation = $this->getAdditionalFactoryConfigurationFileLocation();
        if (file_exists($additionalFactoryConfigurationFileLocation)) {
            $additionalFactoryConfigurationArray = require $additionalFactoryConfigurationFileLocation;
            ArrayUtility::mergeRecursiveWithOverrule(
                $localConfigurationArray,
                $additionalFactoryConfigurationArray
            );
        }
        $randomKey = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(96);
        $localConfigurationArray['SYS']['encryptionKey'] = $randomKey;

        $this->writeLocalConfiguration($localConfigurationArray);
    }

    /**
     * Check if access / write to given path in local configuration is allowed.
     *
     * @param string $path Path to search for
     * @return bool TRUE if access is allowed
     */
    protected function isValidLocalConfigurationPath(string $path): bool
    {
        // Early return for white listed paths
        foreach ($this->allowedSettingsPaths as $allowedSettingsPath) {
            if (str_starts_with($path, $allowedSettingsPath)) {
                return true;
            }
        }
        return ArrayUtility::isValidPath($this->getDefaultConfiguration(), $path);
    }
}
