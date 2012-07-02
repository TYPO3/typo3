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
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Helge Funk <helge.funk@e-net.info>
 */
class t3lib_Configuration {

	/**
	 *
	 */
	const DEFAULT_CONFIGURATION_FILE = 'stddb/DefaultSettings.php';

	/**
	 *
	 */
	const DATABASE_CONFIGURATION_FILE = 'DatabaseConfiguration.php';

	/**
	 *
	 */
	const LOCAL_CONFIGURATION_FILE = 'LocalConfiguration.php';

	/**
	 *
	 */
	const ADDITIONAL_CONFIGURATION_FILE = 'AdditionalConfiguration.php';

	/**
	 *
	 */
	const LOCALCONF_FILE = 'localconf.php';

	/**
	 *
	 */
	public function __construct() {

	}

	/**
	 * @return array
	 */
	protected static function getDefaultConfiguration() {
		return require(PATH_t3lib . t3lib_Configuration::DEFAULT_CONFIGURATION_FILE);
	}

	/**
	 * @return void
	 */
	public static function loadDefaultConfiguration() {
		$GLOBALS['TYPO3_CONF_VARS'] = self::getDefaultConfiguration();
	}

	/**
	 * Loads the configuration
	 *
	 * @static
	 * @throws RuntimeException
	 */
	public static function loadConfiguration() {
		global $TYPO3_CONF_VARS, $typo_db, $typo_db_username, $typo_db_password, $typo_db_host, $typo_db_extTableDef_script;

		if (@is_file(PATH_typo3conf . self::DATABASE_CONFIGURATION_FILE) && @is_file(PATH_typo3conf . self::LOCAL_CONFIGURATION_FILE)) {
			require(PATH_typo3conf . self::DATABASE_CONFIGURATION_FILE);

			$localConfiguration = require(PATH_typo3conf . self::LOCAL_CONFIGURATION_FILE);
			if (is_array($localConfiguration) === TRUE) {
				$TYPO3_CONF_VARS = t3lib_div::array_merge_recursive_overrule(
					$TYPO3_CONF_VARS,
					$localConfiguration
				);
			} else {
				die('LocalConfiguration not found');
			}

			if (@is_file(PATH_typo3conf . self::ADDITIONAL_CONFIGURATION_FILE)) {
				require(PATH_typo3conf . self::ADDITIONAL_CONFIGURATION_FILE);
			}
		} elseif (@is_file(PATH_typo3conf . self::LOCALCONF_FILE)) {
			require(PATH_typo3conf . self::LOCALCONF_FILE);
		} else {
			throw new RuntimeException(self::LOCALCONF_FILE . ' is not found!', 1333754332);
		}
	}

	/**
	 * @param array $configuration
	 * @return boolean
	 */
	protected static function writeLocalConfiguration(array $configuration) {
		$result = file_put_contents(
			PATH_typo3conf . t3lib_Configuration::LOCAL_CONFIGURATION_FILE,
			"<?php\nreturn " . var_export($configuration, TRUE) . ";\n ?>"
		);
		return ($result === FALSE) ? FALSE : TRUE;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public static function getLocalConfiguration() {
		$localConfigurationFilePath = PATH_typo3conf . t3lib_Configuration::LOCAL_CONFIGURATION_FILE;
		if (file_exists($localConfigurationFilePath) === FALSE) {
			throw new Exception();
		}
		return require($localConfigurationFilePath);
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
	 *
	 * @param string $path
	 * @return boolean
	 */
	public static function isValidLocalConfigurationPath($path) {
		return t3lib_utility_Array::isValidPath(
			self::getDefaultConfiguration(),
			$path
		);
	}

	/**
	 * @param $path
	 * @param null $default
	 * @return mixed
	 */
	protected static function getLocalConfigurationValueByPath($path, $default = null)
	{
		return t3lib_utility_Array::getValueByPath(
			self::getLocalConfiguration(),
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
}
?>