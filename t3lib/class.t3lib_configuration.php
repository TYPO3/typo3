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
	public function __construct() {

	}

	/**
	 * @return array
	 */
	public function getDefaultConfiguration() {
		return require(PATH_t3lib . t3lib_Configuration::DEFAULT_CONFIGURATION_FILE);
	}

	/**
	 * @param array $configuration
	 * @return void
	 */
	protected function writeLocalConfiguration(array $configuration) {
		file_put_contents(
			PATH_typo3conf . t3lib_Configuration::LOCAL_CONFIGURATION_FILE,
			"<?php\nreturn " . var_export($configuration, TRUE) . ";\n ?>"
		);
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getLocalConfiguration() {
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
	public function updateLocalConfiguration(array $configurationToMerge) {
		$newLocalConfiguration = t3lib_div::array_merge_recursive_overrule(
			$this->getLocalConfiguration(),
			$configurationToMerge
		);
		$this->writeLocalConfiguration($newLocalConfiguration);
	}

	/**
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function isValidLocalConfigurationPath($path) {
		return t3lib_utility_Array::isValidPath(
			$this->getDefaultConfiguration(),
			$path
		);
	}

	/**
	 * @param $path
	 * @param null $default
	 * @return mixed
	 */
	protected function getLocalConfigurationValueByPath($path, $default = null)
	{
		return t3lib_utility_Array::getValueByPath(
			$this->getLocalConfiguration(),
			$path
		);
	}

	/**
	 *
	 * @param mixed $value
	 * @param string $path
	 * @return boolean
	 */
	public function setLocalConfigurationValueByPath($value, $path) {
		$result = FALSE;
		if ($this->isValidLocalConfigurationPath($path) === TRUE) {
			$result = t3lib_utility_Array::setValueByPath(
				$this->getLocalConfiguration(),
				$path,
				$value
			);
		}
		return $result;
	}
}
?>