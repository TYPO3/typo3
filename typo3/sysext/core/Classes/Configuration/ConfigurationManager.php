<?php
namespace TYPO3\CMS\Core\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Helge Funk <helge.funk@e-net.info>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility;

/**
 * Handle loading and writing of global and local (instance specific)
 * configuration.
 *
 * This class handles the access to the files
 * - EXT:core/Configuration/DefaultConfiguration.php (default TYPO3_CONF_VARS)
 * - typo3conf/LocalConfiguration.php (overrides of TYPO3_CONF_VARS)
 * - typo3conf/AdditionalConfiguration.php (optional additional local code blocks)
 * - typo3conf/localconf.php (legacy configuration file)
 *
 * @author Helge Funk <helge.funk@e-net.info>
 */
class ConfigurationManager {

	/**
	 * @var string Path to default TYPO3_CONF_VARS file, relative to PATH_site
	 */
	protected $defaultConfigurationFile = 'typo3/sysext/core/Configuration/DefaultConfiguration.php';

	/**
	 * @var string Path to local overload TYPO3_CONF_VARS file, relative to PATH_site
	 */
	protected $localConfigurationFile = 'typo3conf/LocalConfiguration.php';

	/**
	 * @var string Path to additional local file, relative to PATH_site
	 */
	protected $additionalConfigurationFile = 'typo3conf/AdditionalConfiguration.php';

	/**
	 * @var string Path to factory configuration file used during installation as LocalConfiguration boilerplate
	 */
	protected $factoryConfigurationFile = 'typo3/sysext/core/Configuration/FactoryConfiguration.php';

	/**
	 * @var string Path to possible additional factory configuration file delivered by packages
	 */
	protected $additionalFactoryConfigurationFile = 'typo3conf/AdditionalFactoryConfiguration.php';

	/**
	 * @var string Absolute path to typo3conf directory
	 */
	protected $pathTypo3Conf = PATH_typo3conf;

	/**
	 * Writing to these configuration pathes is always allowed,
	 * even if the requested sub path does not exist yet.
	 *
	 * @var array
	 */
	protected $whiteListedLocalConfigurationPaths = array(
		'EXT/extConf',
		'EXTCONF',
		'INSTALL/wizardDone',
		'DB',
		'SYS/caching/cacheConfigurations',
	);

	/**
	 * Return default configuration array
	 *
	 * @return array
	 */
	public function getDefaultConfiguration() {
		return require $this->getDefaultConfigurationFileLocation();
	}

	/**
	 * Get the file location of the default configuration file,
	 * currently the path and filename.
	 *
	 * @return string
	 * @access private
	 */
	public function getDefaultConfigurationFileLocation() {
		return PATH_site . $this->defaultConfigurationFile;
	}

	/**
	 * Return local configuration array typo3conf/LocalConfiguration.php
	 *
	 * @return array Content array of local configuration file
	 */
	public function getLocalConfiguration() {
		return require $this->getLocalConfigurationFileLocation();
	}

	/**
	 * Get the file location of the local configuration file,
	 * currently the path and filename.
	 *
	 * @return string
	 * @access private
	 */
	public function getLocalConfigurationFileLocation() {
		return PATH_site . $this->localConfigurationFile;
	}

	/**
	 * Get the file location of the additional configuration file,
	 * currently the path and filename.
	 *
	 * @return string
	 * @access private
	 */
	public function getAdditionalConfigurationFileLocation() {
		return PATH_site . $this->additionalConfigurationFile;
	}

	/**
	 * Get absolute file location of factory configuration file
	 *
	 * @return string
	 */
	protected function getFactoryConfigurationFileLocation() {
		return PATH_site . $this->factoryConfigurationFile;
	}

	/**
	 * Get absolute file location of factory configuration file
	 *
	 * @return string
	 */
	protected function getAdditionalFactoryConfigurationFileLocation() {
		return PATH_site . $this->additionalFactoryConfigurationFile;
	}

	/**
	 * Override local configuration with new values.
	 *
	 * @param array $configurationToMerge Override configuration array
	 * @return void
	 */
	public function updateLocalConfiguration(array $configurationToMerge) {
		$newLocalConfiguration = $this->getLocalConfiguration();
		Utility\ArrayUtility::mergeRecursiveWithOverrule($newLocalConfiguration, $configurationToMerge);
		$this->writeLocalConfiguration($newLocalConfiguration);
	}

	/**
	 * Get a value at given path from default configuration
	 *
	 * @param string $path Path to search for
	 * @return mixed Value at path
	 */
	public function getDefaultConfigurationValueByPath($path) {
		return Utility\ArrayUtility::getValueByPath($this->getDefaultConfiguration(), $path);
	}

	/**
	 * Get a value at given path from local configuration
	 *
	 * @param string $path Path to search for
	 * @return mixed Value at path
	 */
	public function getLocalConfigurationValueByPath($path) {
		return Utility\ArrayUtility::getValueByPath($this->getLocalConfiguration(), $path);
	}

	/**
	 * Get a value from configuration, this is default configuration
	 * merged with local configuration
	 *
	 * @param string $path Path to search for
	 * @return mixed
	 */
	public function getConfigurationValueByPath($path) {
		$defaultConfiguration = $this->getDefaultConfiguration();
		Utility\ArrayUtility::mergeRecursiveWithOverrule($defaultConfiguration, $this->getLocalConfiguration());
		return Utility\ArrayUtility::getValueByPath($defaultConfiguration, $path);
	}

	/**
	 * Update a given path in local configuration to a new value.
	 *
	 * @param string $path Path to update
	 * @param mixed $value Value to set
	 * @return boolean TRUE on success
	 */
	public function setLocalConfigurationValueByPath($path, $value) {
		$result = FALSE;
		if ($this->isValidLocalConfigurationPath($path)) {
			$localConfiguration = $this->getLocalConfiguration();
			$localConfiguration = Utility\ArrayUtility::setValueByPath($localConfiguration, $path, $value);
			$result = $this->writeLocalConfiguration($localConfiguration);
		}
		return $result;
	}

	/**
	 * Update / set a list of path and value pairs in local configuration file
	 *
	 * @param array $pairs Key is path, value is value to set
	 * @return boolean TRUE on success
	 */
	public function setLocalConfigurationValuesByPathValuePairs(array $pairs) {
		$localConfiguration = $this->getLocalConfiguration();
		foreach ($pairs as $path => $value) {
			if ($this->isValidLocalConfigurationPath($path)) {
				$localConfiguration = Utility\ArrayUtility::setValueByPath($localConfiguration, $path, $value);
			}
		}
		return $this->writeLocalConfiguration($localConfiguration);
	}

	/**
	 * Remove keys from LocalConfiguration
	 *
	 * @param array $keys Array with key paths to remove from LocalConfiguration
	 * @return boolean TRUE if something was removed
	 */
	public function removeLocalConfigurationKeysByPath(array $keys) {
		$result = FALSE;
		$localConfiguration = $this->getLocalConfiguration();
		foreach ($keys as $path) {
			// Remove key if path is within LocalConfiguration
			if (Utility\ArrayUtility::isValidPath($localConfiguration, $path)) {
				$result = TRUE;
				$localConfiguration = Utility\ArrayUtility::removeByPath($localConfiguration, $path);
			}
		}
		if ($result) {
			$this->writeLocalConfiguration($localConfiguration);
		}
		return $result;
	}

	/**
	 * Checks if the configuration can be written.
	 *
	 * @return boolean
	 * @access private
	 */
	public function canWriteConfiguration() {
		$fileLocation = $this->getLocalConfigurationFileLocation();
		return @is_writable($this->pathTypo3Conf) && (!file_exists($fileLocation) || @is_writable($fileLocation));
	}

	/**
	 * Reads the configuration array and exports it to the global variable
	 *
	 * @access private
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	public function exportConfiguration() {
		if (@is_file($this->getLocalConfigurationFileLocation())) {
			$localConfiguration = $this->getLocalConfiguration();
			if (is_array($localConfiguration)) {
				$defaultConfiguration = $this->getDefaultConfiguration();
				Utility\ArrayUtility::mergeRecursiveWithOverrule($defaultConfiguration, $localConfiguration);
				$GLOBALS['TYPO3_CONF_VARS'] = $defaultConfiguration;
			} else {
				throw new \UnexpectedValueException('LocalConfiguration invalid.', 1349272276);
			}
			if (@is_file($this->getAdditionalConfigurationFileLocation())) {
				require $this->getAdditionalConfigurationFileLocation();
			}
		} else {
			// No LocalConfiguration (yet), load DefaultConfiguration only
			$GLOBALS['TYPO3_CONF_VARS'] = $this->getDefaultConfiguration();
		}
	}

	/**
	 * Write local configuration array to typo3conf/LocalConfiguration.php
	 *
	 * @param array $configuration The local configuration to be written
	 * @throws \RuntimeException
	 * @return boolean TRUE on success
	 * @access private
	 */
	public function writeLocalConfiguration(array $configuration) {
		$localConfigurationFile = $this->getLocalConfigurationFileLocation();
		if (!$this->canWriteConfiguration()) {
			throw new \RuntimeException(
				$localConfigurationFile . ' is not writable.', 1346323822
			);
		}
		$configuration = Utility\ArrayUtility::sortByKeyRecursive($configuration);
		$result = Utility\GeneralUtility::writeFile(
			$localConfigurationFile,
			'<?php' . LF .
				'return ' .
					Utility\ArrayUtility::arrayExport(
						Utility\ArrayUtility::renumberKeysToAvoidLeapsIfKeysAreAllNumeric($configuration)
					) .
				';' . LF .
			'?>',
			TRUE
		);

		Utility\OpcodeCacheUtility::clearAllActive($localConfigurationFile);

		return $result;
	}

	/**
	 * Write additional configuration array to typo3conf/AdditionalConfiguration.php
	 *
	 * @param array $additionalConfigurationLines The configuration lines to be written
	 * @throws \RuntimeException
	 * @return boolean TRUE on success
	 * @access private
	 */
	public function writeAdditionalConfiguration(array $additionalConfigurationLines) {
		return Utility\GeneralUtility::writeFile(
			PATH_site . $this->additionalConfigurationFile,
			'<?php' . LF .
				implode(LF, $additionalConfigurationLines) . LF .
			'?>'
		);
	}

	/**
	 * Uses FactoryConfiguration file and a possible AdditionalFactoryConfiguration
	 * file in typo3conf to create a basic LocalConfiguration.php. This is used
	 * by the install tool in an early step.
	 *
	 * @throws \RuntimeException
	 * @return void
	 * @access private
	 */
	public function createLocalConfigurationFromFactoryConfiguration() {
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
			Utility\ArrayUtility::mergeRecursiveWithOverrule(
				$localConfigurationArray,
				$additionalFactoryConfigurationArray
			);
		}
		$this->writeLocalConfiguration($localConfigurationArray);
	}

	/**
	 * Check if access / write to given path in local configuration is allowed.
	 *
	 * @param string $path Path to search for
	 * @return boolean TRUE if access is allowed
	 */
	protected function isValidLocalConfigurationPath($path) {
		// Early return for white listed paths
		foreach ($this->whiteListedLocalConfigurationPaths as $whiteListedPath) {
			if (Utility\GeneralUtility::isFirstPartOfStr($path, $whiteListedPath)) {
				return TRUE;
			}
		}
		return Utility\ArrayUtility::isValidPath($this->getDefaultConfiguration(), $path);
	}

}
