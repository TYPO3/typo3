<?php
namespace TYPO3\CMS\Core\Configuration;

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
 * - typo3conf/LocalConfiguration.php (overrides of TYPO3_CONF_VARS)
 * - typo3conf/AdditionalConfiguration.php (optional additional local code blocks)
 *
 * IMPORTANT:
 *   This class is intended for internal core use ONLY.
 *   Extensions should usually use the resulting $GLOBALS['TYPO3_CONF_VARS'] array,
 *   do not try to modify settings in LocalConfiguration.php with an extension.
 * @internal
 */
class ConfigurationManager
{
    /**
     * @var string Path to default TYPO3_CONF_VARS file, relative to the public web folder
     */
    protected $defaultConfigurationFile = 'core/Configuration/DefaultConfiguration.php';

    /**
     * @var string Path to description file for TYPO3_CONF_VARS, relative to the public web folder
     */
    protected $defaultConfigurationDescriptionFile = 'core/Configuration/DefaultConfigurationDescription.yaml';

    /**
     * @var string Path to local overload TYPO3_CONF_VARS file, relative to the public web folder
     */
    protected $localConfigurationFile = 'LocalConfiguration.php';

    /**
     * @var string Path to additional local file, relative to the public web folder
     */
    protected $additionalConfigurationFile = 'AdditionalConfiguration.php';

    /**
     * @var string Path to factory configuration file used during installation as LocalConfiguration boilerplate
     */
    protected $factoryConfigurationFile = 'core/Configuration/FactoryConfiguration.php';

    /**
     * @var string Path to possible additional factory configuration file delivered by packages
     */
    protected $additionalFactoryConfigurationFile = 'AdditionalFactoryConfiguration.php';

    /**
     * Writing to these configuration paths is always allowed,
     * even if the requested sub path does not exist yet.
     *
     * @var array
     */
    protected $whiteListedLocalConfigurationPaths = [
        'EXT/extConf',
        'EXTCONF',
        'DB',
        'SYS/caching/cacheConfigurations',
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
        return Environment::getFrameworkBasePath() . '/' . $this->defaultConfigurationFile;
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
        return Environment::getFrameworkBasePath() . '/' . $this->defaultConfigurationDescriptionFile;
    }

    /**
     * Return local configuration array typo3conf/LocalConfiguration.php
     *
     * @return array Content array of local configuration file
     */
    public function getLocalConfiguration()
    {
        return require $this->getLocalConfigurationFileLocation();
    }

    /**
     * Get the file location of the local configuration file,
     * currently the path and filename.
     *
     * @return string
     * @internal
     */
    public function getLocalConfigurationFileLocation()
    {
        return Environment::getLegacyConfigPath() . '/' . $this->localConfigurationFile;
    }

    /**
     * Returns local configuration array merged with default configuration
     *
     * @return array
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
        return Environment::getLegacyConfigPath() . '/' . $this->additionalConfigurationFile;
    }

    /**
     * Get absolute file location of factory configuration file
     *
     * @return string
     */
    protected function getFactoryConfigurationFileLocation()
    {
        return Environment::getFrameworkBasePath() . '/' . $this->factoryConfigurationFile;
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
    public function removeLocalConfigurationKeysByPath(array $keys)
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
     * Enables a certain feature and writes the option to LocalConfiguration.php
     * Short-hand method
     *
     * @param string $featureName something like "InlineSvgImages"
     * @return bool true on successful writing the setting
     */
    public function enableFeature(string $featureName): bool
    {
        return $this->setLocalConfigurationValueByPath('SYS/features/' . $featureName, true);
    }

    /**
     * Disables a feature and writes the option to LocalConfiguration.php
     * Short-hand method
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
        $fileLocation = $this->getLocalConfigurationFileLocation();
        return @is_writable(file_exists($fileLocation) ? $fileLocation : Environment::getLegacyConfigPath() . '/');
    }

    /**
     * Reads the configuration array and exports it to the global variable
     *
     * @internal
     * @throws \UnexpectedValueException
     */
    public function exportConfiguration()
    {
        if (@is_file($this->getLocalConfigurationFileLocation())) {
            $localConfiguration = $this->getLocalConfiguration();
            if (is_array($localConfiguration)) {
                $defaultConfiguration = $this->getDefaultConfiguration();
                ArrayUtility::mergeRecursiveWithOverrule($defaultConfiguration, $localConfiguration);
                $GLOBALS['TYPO3_CONF_VARS'] = $defaultConfiguration;
            } else {
                throw new \UnexpectedValueException('LocalConfiguration invalid.', 1349272276);
            }
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
     * Write local configuration array to typo3conf/LocalConfiguration.php
     *
     * @param array $configuration The local configuration to be written
     * @throws \RuntimeException
     * @return bool TRUE on success
     * @internal
     */
    public function writeLocalConfiguration(array $configuration)
    {
        $localConfigurationFile = $this->getLocalConfigurationFileLocation();
        if (!$this->canWriteConfiguration()) {
            throw new \RuntimeException(
                $localConfigurationFile . ' is not writable.',
                1346323822
            );
        }
        $configuration = ArrayUtility::sortByKeyRecursive($configuration);
        $result = GeneralUtility::writeFile(
            $localConfigurationFile,
            "<?php\n" .
                'return ' .
                    ArrayUtility::arrayExport($configuration) .
                ";\n",
            true
        );

        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($localConfigurationFile);

        return $result;
    }

    /**
     * Write additional configuration array to typo3conf/AdditionalConfiguration.php
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
            "<?php\n" . implode("\n", $additionalConfigurationLines) . "\n"
        );
    }

    /**
     * Uses FactoryConfiguration file and a possible AdditionalFactoryConfiguration
     * file in typo3conf to create a basic LocalConfiguration.php. This is used
     * by the install tool in an early step.
     *
     * @throws \RuntimeException
     * @internal
     */
    public function createLocalConfigurationFromFactoryConfiguration()
    {
        if (file_exists($this->getLocalConfigurationFileLocation())) {
            throw new \RuntimeException(
                'LocalConfiguration.php exists already',
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
    protected function isValidLocalConfigurationPath($path)
    {
        // Early return for white listed paths
        foreach ($this->whiteListedLocalConfigurationPaths as $whiteListedPath) {
            if (GeneralUtility::isFirstPartOfStr($path, $whiteListedPath)) {
                return true;
            }
        }
        return ArrayUtility::isValidPath($this->getDefaultConfiguration(), $path);
    }
}
