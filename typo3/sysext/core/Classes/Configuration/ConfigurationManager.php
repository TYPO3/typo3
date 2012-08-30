<?php
namespace TYPO3\CMS\Core\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Helge Funk <helge.funk@e-net.info>
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
/**
 * Handle loading and writing of global and local (instance specific)
 * configuration.
 *
 * This class handles the access to the files
 * - t3lib/stddb/DefaultConfiguration.php (default TYPO3_CONF_VARS)
 * - typo3conf/LocalConfiguration.php (overrides of TYPO3_CONF_VARS)
 * - typo3conf/AdditionalConfiguration.php (optional additional local code blocks)
 * - typo3conf/localconf.php (legacy configuration file)
 *
 * @package TYPO3
 * @subpackage t3lib
 * @author Helge Funk <helge.funk@e-net.info>
 */
class ConfigurationManager {

	/**
	 * Path to default TYPO3_CONF_VARS file, relative to PATH_site
	 */
	const DEFAULT_CONFIGURATION_FILE = 't3lib/stddb/DefaultConfiguration.php';
	/**
	 * Path to local overload TYPO3_CONF_VARS file, relative to PATH_site
	 */
	const LOCAL_CONFIGURATION_FILE = 'typo3conf/LocalConfiguration.php';
	/**
	 * Path to additional local file, relative to PATH_site
	 */
	const ADDITIONAL_CONFIGURATION_FILE = 'typo3conf/AdditionalConfiguration.php';
	/**
	 * Path to legacy localconf.php file, relative to PATH_site
	 */
	const LOCALCONF_FILE = 'typo3conf/localconf.php';
	/**
	 * Writing to these configuration pathes is always allowed,
	 * even if the requested sub path does not exist yet.
	 *
	 * @var array
	 */
	static protected $whiteListedLocalConfigurationPaths = array(
		'EXT/extConf',
		'EXTCONF',
		'INSTALL/wizardDone',
		'DB'
	);

	/**
	 * Return default configuration array t3lib/stddb/DefaultConfiguration.php
	 *
	 * @return array
	 */
	static public function getDefaultConfiguration() {
		return require PATH_site . static::DEFAULT_CONFIGURATION_FILE;
	}

	/**
	 * Return local configuration array typo3conf/LocalConfiguration.php
	 *
	 * @return array Content array of local configuration file
	 */
	static public function getLocalConfiguration() {
		return require PATH_site . static::LOCAL_CONFIGURATION_FILE;
	}

	/**
	 * Override local configuration with new values.
	 *
	 * @param array $configurationToMerge Override configuration array
	 * @return void
	 */
	static public function updateLocalConfiguration(array $configurationToMerge) {
		$newLocalConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(static::getLocalConfiguration(), $configurationToMerge);
		static::writeLocalConfiguration($newLocalConfiguration);
	}

	/**
	 * Get a value at given path from default configuration
	 *
	 * @param string $path Path to search for
	 * @return mixed Value at path
	 */
	static public function getDefaultConfigurationValueByPath($path) {
		return \TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath(static::getDefaultConfiguration(), $path);
	}

	/**
	 * Get a value at given path from local configuration
	 *
	 * @param string $path Path to search for
	 * @return mixed Value at path
	 */
	static public function getLocalConfigurationValueByPath($path) {
		return \TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath(static::getLocalConfiguration(), $path);
	}

	/**
	 * Get a value from configuration, this is default configuration
	 * merged with local configuration
	 *
	 * @param string $path Path to search for
	 * @return mixed
	 */
	static public function getConfigurationValueByPath($path) {
		return \TYPO3\CMS\Core\Utility\ArrayUtility::getValueByPath(\TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(static::getDefaultConfiguration(), static::getLocalConfiguration()), $path);
	}

	/**
	 * Update a given path in local configuration to a new value.
	 *
	 * @param string $path Path to update
	 * @param mixed $value Value to set
	 * @return boolean TRUE on success
	 */
	static public function setLocalConfigurationValueByPath($path, $value) {
		$result = FALSE;
		if (static::isValidLocalConfigurationPath($path)) {
			$localConfiguration = static::getLocalConfiguration();
			$localConfiguration = \TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath($localConfiguration, $path, $value);
			$result = static::writeLocalConfiguration($localConfiguration);
		}
		return $result;
	}

	/**
	 * Update / set a list of path and value pairs in local configuration file
	 *
	 * @param array $pairs Key is path, value is value to set
	 * @return boolean TRUE on success
	 */
	static public function setLocalConfigurationValuesByPathValuePairs(array $pairs) {
		$localConfiguration = static::getLocalConfiguration();
		foreach ($pairs as $path => $value) {
			if (static::isValidLocalConfigurationPath($path)) {
				$localConfiguration = \TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath($localConfiguration, $path, $value);
			}
		}
		return static::writeLocalConfiguration($localConfiguration);
	}

	/**
	 * Write local configuration array to typo3conf/LocalConfiguration.php
	 *
	 * @param array $configuration The local configuration to be written
	 * @throws \RuntimeException
	 * @return boolean TRUE on success
	 */
	static protected function writeLocalConfiguration(array $configuration) {
		$localConfigurationFile = PATH_site . static::LOCAL_CONFIGURATION_FILE;
		if (!@is_file($localConfigurationFile) || !@is_writable($localConfigurationFile)) {
			throw new \RuntimeException($localConfigurationFile . ' does not exist or is not writable.', 1346323822);
		}
		$configuration = \TYPO3\CMS\Core\Utility\ArrayUtility::sortByKeyRecursive($configuration);
		$result = \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(
			$localConfigurationFile,
			'<?php' . LF . 'return ' . \TYPO3\CMS\Core\Utility\ArrayUtility::arrayExport($configuration) . ';' . LF . '?>'
		);
		return $result === FALSE ? FALSE : TRUE;
	}

	/**
	 * Check if access / write to given path in local configuration is allowed.
	 *
	 * @param string $path Path to search for
	 * @return boolean TRUE if access is allowed
	 */
	static protected function isValidLocalConfigurationPath($path) {
		// Early return for white listed paths
		foreach (static::$whiteListedLocalConfigurationPaths as $whiteListedPath) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($path, $whiteListedPath)) {
				return TRUE;
			}
		}
		return \TYPO3\CMS\Core\Utility\ArrayUtility::isValidPath(static::getDefaultConfiguration(), $path);
	}

}


?>