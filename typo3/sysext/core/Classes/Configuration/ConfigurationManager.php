<?php
namespace TYPO3\CMS\Core\Configuration;
use TYPO3\CMS\Core\Utility;

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
	 * @var string Path to legacy localconf.php file, relative to PATH_site
	 */
	protected $localconfFile = 'typo3conf/localconf.php';

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
		'DB'
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
	 * Get the file resource
	 *
	 * @return string
	 * @deprecated since 6.1, will be removed if the compatibily layer for localconf.php is dropped
	 */
	public function getLocalconfFileLocation() {
		return PATH_site . $this->localconfFile;
	}

	/**
	 * Override local configuration with new values.
	 *
	 * @param array $configurationToMerge Override configuration array
	 * @return void
	 */
	public function updateLocalConfiguration(array $configurationToMerge) {
		$newLocalConfiguration = Utility\GeneralUtility::array_merge_recursive_overrule(
			$this->getLocalConfiguration(),
			$configurationToMerge
		);
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
		return Utility\ArrayUtility::getValueByPath(
			Utility\GeneralUtility::array_merge_recursive_overrule(
				$this->getDefaultConfiguration(), $this->getLocalConfiguration()
			),
			$path
		);
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
	 * Checks if the configuration can be written.
	 *
	 * @return boolean
	 * @access private
	 */
	public function canWriteConfiguration() {
		$result = TRUE;
		if (!@is_writable($this->pathTypo3Conf)) {
			$result = FALSE;
		}
		if (
			file_exists($this->getLocalConfigurationFileLocation())
			&& !@is_writable($this->getLocalConfigurationFileLocation())
		) {
			$result = FALSE;
		}
		if (
			file_exists($this->getLocalconfFileLocation())
			&& !@is_writable($this->getLocalconfFileLocation())
		) {
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * Reads the configuration array and exports it to the global variable
	 *
	 * @access private
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	public function exportConfiguration() {
		if (@is_file($this->getLocalConfigurationFileLocation())) {
			$localConfiguration = $this->getLocalConfiguration();
			if (is_array($localConfiguration)) {
				$GLOBALS['TYPO3_CONF_VARS'] = Utility\GeneralUtility::array_merge_recursive_overrule($this->getDefaultConfiguration(), $localConfiguration);
			} else {
				throw new \UnexpectedValueException('LocalConfiguration invalid.', 1349272276);
			}
			if (@is_file($this->getAdditionalConfigurationFileLocation())) {
				require $this->getAdditionalConfigurationFileLocation();
			}
			// @deprecated since 6.0: Simulate old 'extList' as comma separated list of 'extListArray'
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = implode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray']);
		} elseif (@is_file($this->getLocalconfFileLocation())) {
			$GLOBALS['TYPO3_CONF_VARS'] = $this->getDefaultConfiguration();
			// Legacy localconf.php handling
			// @deprecated: Can be removed if old localconf.php is not supported anymore
			global $TYPO3_CONF_VARS, $typo_db, $typo_db_username, $typo_db_password, $typo_db_host, $typo_db_extTableDef_script;
			require $this->getLocalconfFileLocation();
			// If the localconf.php was not upgraded to LocalConfiguration.php, the default extListArray
			// from EXT:core/Configuration/DefaultConfiguration.php is still set. In this case we just unset
			// this key here, so \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray() falls back to use extList string
			// @deprecated: This case can be removed later if localconf.php is not supported anymore
			unset($TYPO3_CONF_VARS['EXT']['extListArray']);
			// Write the old globals into the new place in the configuration array
			$GLOBALS['TYPO3_CONF_VARS']['DB'] = array();
			$GLOBALS['TYPO3_CONF_VARS']['DB']['database'] = $typo_db;
			$GLOBALS['TYPO3_CONF_VARS']['DB']['username'] = $typo_db_username;
			$GLOBALS['TYPO3_CONF_VARS']['DB']['password'] = $typo_db_password;
			$GLOBALS['TYPO3_CONF_VARS']['DB']['host'] = $typo_db_host;
			$GLOBALS['TYPO3_CONF_VARS']['DB']['extTablesDefinitionScript'] = $typo_db_extTableDef_script;
			unset($GLOBALS['typo_db']);
			unset($GLOBALS['typo_db_username']);
			unset($GLOBALS['typo_db_password']);
			unset($GLOBALS['typo_db_host']);
			unset($GLOBALS['typo_db_extTableDef_script']);
		} else {
			throw new \RuntimeException(
				'Neither ' . $this->localConfigurationFile . ' (recommended) nor ' . $this->localconfFile . ' (obsolete) could be found!',
				1349272337
			);
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
		return $result === FALSE ? FALSE : TRUE;
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
		$result = Utility\GeneralUtility::writeFile(
			PATH_site . $this->additionalConfigurationFile,
			'<?php' . LF .
				implode(LF, $additionalConfigurationLines) . LF .
			'?>'
		);
		return $result === FALSE ? FALSE : TRUE;
	}

	/**
	 * Uses FactoryConfiguration file and a possible AdditionalFactoryConfiguration
	 * file in typo3conf to create a basic LocalConfiguration.php. This is used
	 * by the install tool in an early step.
	 *
	 * @return void
	 * @access private
	 */
	public function createLocalConfigurationFromFactoryConfiguration() {
		if (file_exists($this->getLocalConfigurationFileLocation())) {
			throw new \RuntimeException(
				'LocalConfiguration.php exists already', 1364836026
			);
		}
		$localConfigurationArray = require $this->getFactoryConfigurationFileLocation();
		$additionalFactoryConfigurationFileLocation = $this->getAdditionalFactoryConfigurationFileLocation();
		if (file_exists($additionalFactoryConfigurationFileLocation)) {
			$additionalFactoryConfigurationArray = require $additionalFactoryConfigurationFileLocation;
			$localConfigurationArray = Utility\GeneralUtility::array_merge_recursive_overrule(
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


?>