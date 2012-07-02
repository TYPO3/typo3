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

require(PATH_t3lib . 'utility/class.t3lib_utility_array.php');

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
	const DEFAULT_CONFIGURATION_FILE = 'stddb/DefaultConfiguration.php';

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
	 * @var array
	 */
	protected static $whiteListedLocalConfigurationPaths = array(
		'EXT/extConf',
		'INSTALL/wizardDone'
	);

	/**
	 * @return array
	 */
	protected static function getDefaultConfiguration() {
		return require(PATH_t3lib . t3lib_Configuration::DEFAULT_CONFIGURATION_FILE);
	}

	/**
	 * Set global database variables to empty string.
	 * Database-variables are cleared!
	 *
	 * @TODO: Figure out why we do this (security reasons with register globals?)
	 * @return Typo3_Bootstrap
	 */
	protected static function setGlobalDatabaseVariablesToEmptyString() {
		// The database name
		$GLOBALS['typo_db'] = '';
		// The database username
		$GLOBALS['typo_db_username'] = '';
		// The database password
		$GLOBALS['typo_db_password'] = '';
		// The database host
		$GLOBALS['typo_db_host'] = '';
		// The filename of an additional script in typo3conf/-folder which is included after
		// tables.php. Code in this script should modify the tables.php-configuration only,
		// and this provides a good way to extend the standard-distributed tables.php file.
		$GLOBALS['typo_db_extTableDef_script'] = '';
	}

	/**
	 * Define the database setup as constants
	 * and unset no longer needed global variables
	 */
	protected static function defineDatabaseConstants() {
		define('TYPO3_db', $GLOBALS['typo_db']);
		define('TYPO3_db_username', $GLOBALS['typo_db_username']);
		define('TYPO3_db_password', $GLOBALS['typo_db_password']);
		define('TYPO3_db_host', $GLOBALS['typo_db_host']);
		define('TYPO3_extTableDef_script', $GLOBALS['typo_db_extTableDef_script']);
		define('TYPO3_user_agent', 'User-Agent: '. $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);

		unset($GLOBALS['typo_db']);
		unset($GLOBALS['typo_db_username']);
		unset($GLOBALS['typo_db_password']);
		unset($GLOBALS['typo_db_host']);
		unset($GLOBALS['typo_db_extTableDef_script']);
	}

	/**
	 * @return void
	 */
	public static function loadDefaultConfiguration() {
		$defaultConfiguration = self::getDefaultConfiguration();
		t3lib_utility_Array::sortByKeyRecursive($defaultConfiguration);
		$GLOBALS['TYPO3_CONF_VARS'] = $defaultConfiguration;
		self::setGlobalDatabaseVariablesToEmptyString();
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
		self::defineDatabaseConstants();
	}

	/**
	 * @param array $configuration
	 * @return boolean
	 */
	protected static function writeLocalConfiguration(array $configuration) {
		t3lib_utility_Array::sortByKeyRecursive($configuration);
		$result = t3lib_div::writeFile(
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

	/**
	 * @param $path
	 * @return mixed
	 */
	public static function getDefaultConfigurationValueByPath($path)
	{
		return t3lib_utility_Array::getValueByPath(
			self::getDefaultConfiguration(),
			$path
		);
	}

	/**
	 * @param $path
	 * @return mixed
	 */
	public static function getLocalConfigurationValueByPath($path)
	{
		return t3lib_utility_Array::getValueByPath(
			self::getLocalConfiguration(),
			$path
		);
	}

	/**
	 * @param $path
	 * @return mixed
	 */
	public static function getConfigurationValueByPath($path)
	{
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
}
?>