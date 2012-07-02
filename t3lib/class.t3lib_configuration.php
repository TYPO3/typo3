<?php
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
class t3lib_Configuration {

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
	 * Path to legacy localcon.php file, relative to PATH_site
	 */
	const LOCALCONF_FILE = 'typo3conf/localconf.php';

	/**
	 * @var array
	 */
	protected static $whiteListedLocalConfigurationPaths = array(
		'EXT/extConf',
		'INSTALL/wizardDone'
	);

	/**
	 * Gets the default configuration array without local changes.
	 *
	 * @return array
	 */
	public static function getDefaultConfiguration() {
		return require(PATH_site . self::DEFAULT_CONFIGURATION_FILE);
	}

	/**
	 * Return local configuration array typo3conf/LocalConfiguration.php
	 *
	 * @return array Content array of local configuration file
	 */
	public static function getLocalConfiguration() {
		return require(PATH_site . t3lib_Configuration::LOCAL_CONFIGURATION_FILE);
	}

	/**
	 * @param array $configurationToMerge
	 * @return void
	 */
	public static function updateLocalConfiguration(array $configurationToMerge) {
		$newLocalConfiguration = t3lib_div::array_merge_recursive_overrule(
			self::getLocalConfiguration(),
			$configurationToMerge
		);
		self::writeLocalConfiguration($newLocalConfiguration);
	}

	/**
	 * @param $path
	 * @return mixed
	 */
	public static function getDefaultConfigurationValueByPath($path) {
		return t3lib_utility_Array::getValueByPath(
			self::getDefaultConfiguration(),
			$path
		);
	}

	/**
	 * @param $path
	 * @return mixed
	 */
	public static function getLocalConfigurationValueByPath($path) {
		return t3lib_utility_Array::getValueByPath(
			self::getLocalConfiguration(),
			$path
		);
	}

	/**
	 * @param $path
	 * @return mixed
	 */
	public static function getConfigurationValueByPath($path) {
		return t3lib_utility_Array::getValueByPath(
			t3lib_div::array_merge_recursive_overrule(
				self::getDefaultConfiguration(),
				self::getLocalConfiguration()
			),
			$path
		);
	}

	/**
	 *
	 * @param mixed $value
	 * @param string $path
	 * @return boolean
	 */
	public static function setLocalConfigurationValueByPath($value, $path) {
		$result = FALSE;
		if (self::isValidLocalConfigurationPath($path) === TRUE) {
			$localConfiguration = self::getLocalConfiguration();
			$localConfiguration = t3lib_utility_Array::setValueByPath(
				$localConfiguration,
				$path,
				$value
			);
			$result = self::writeLocalConfiguration($localConfiguration);
		}
		return $result;
	}

	/**
	 *
	 * @param array $pairs
	 * @return boolean
	 */
	public static function setLocalConfigurationValuesByPathValuePairs(array $pairs) {
		$localConfiguration = self::getLocalConfiguration();
		foreach ($pairs as $path => $value) {
			if (self::isValidLocalConfigurationPath($path) === TRUE) {
				$localConfiguration = t3lib_utility_Array::setValueByPath(
					$localConfiguration,
					$path,
					$value
				);
			}
		}
		return self::writeLocalConfiguration($localConfiguration);
	}

	/**
	 * Write local configuration array to typo3conf/LocalConfiguration.php
	 *
	 * @param array $configuration The local configuration to be written
	 * @return boolean True on success
	 */
	protected static function writeLocalConfiguration(array $configuration) {
		t3lib_utility_Array::sortByKeyRecursive($configuration);
		$result = t3lib_div::writeFile(
			PATH_typo3conf . self::LOCAL_CONFIGURATION_FILE,
			"<?php\nreturn " . var_export($configuration, TRUE) . ";\n ?>"
		);
		return ($result === FALSE) ? FALSE : TRUE;
	}

	/**
	 *
	 * @param string $path
	 * @return boolean
	 */
	protected static function isValidLocalConfigurationPath($path) {
			// Early return for white listed paths
		foreach (self::$whiteListedLocalConfigurationPaths as $whiteListedPath) {
			if (mb_strpos($whiteListedPath, $path) === 0) {
				return TRUE;
			}
		}

		return t3lib_utility_Array::isValidPath(
			self::getDefaultConfiguration(),
			$path
		);
	}
}
?>