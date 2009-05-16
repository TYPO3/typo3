<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * A general purpose configuration manager
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_Configuration_Manager implements t3lib_Singleton {

	/**
	 * Storage for the settings, loaded by loadGlobalSettings()
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * The configuration source instances used for loading the raw configuration
	 *
	 * @var array
	 */
	protected $configurationSources;

	/**
	 * Constructs the configuration manager
	 *
	 * @param array $configurationSourcesObjectNames An array of object names of the configuration sources
	 */
	public function __construct(array $configurationSources = array()) {
		$this->configurationSources = $configurationSources;
	}

	/**
	 * Returns an array with the settings defined for the specified extension.
	 *
	 * @param string $extensionName Name of the extension to return the settings for
	 * @return array The settings of the specified extension
	 */
	public function getSettings($extensionName, $controllerName = '', $actionName = '') {
		if (empty($this->settings[$extensionName])) {
			$this->loadGlobalSettings($extensionName);
		}
		$settings = $this->settings[$extensionName];
		if (!empty($controllerName) && is_array($settings[$controllerName])) {
			if (!empty($actionName) && is_array($settings[$controllerName][$actionName])) {
				$settings = $settings[$controllerName][$actionName];
			} else {
				$settings = $settings[$controllerName];
			}
		}
		return $settings;
	}

	/**
	 * Loads the settings defined in the specified extensions and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's settings registry
	 * and can be retrieved with the getSettings() method.
	 *
	 * @param string $extensionName
	 * @return void
	 * @see getSettings()
	 */
	protected function loadGlobalSettings($extensionName) {
		$settings = array();
		foreach ($this->configurationSources as $configurationSource) {
			$settings = t3lib_div::array_merge_recursive_overrule($settings, $configurationSource->load($extensionName));
		}
		$this->settings[$extensionName] = $settings;
	}

}
?>