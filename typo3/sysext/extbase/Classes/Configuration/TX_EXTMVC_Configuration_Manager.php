<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');

/**
 * A general purpose configuration manager
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Configuration_Manager implements t3lib_Singleton {

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
	public function __construct(array $configurationSources) {
		$this->configurationSources = $configurationSources;
	}

	/**
	 * Returns an array with the settings defined for the specified extension.
	 *
	 * @param string $extensionKey Key of the extension to return the settings for
	 * @return array The settings of the specified extension
	 */
	public function getSettings($extensionKey, $controllerName = '', $actionName = '') {
		$settings = array();
		if (is_array($this->settings[$extensionKey])) {
			$settings = $this->settings[$extensionKey];
			if (!empty($controllerName) && is_array($settings[$controllerName])) {
				if (!empty($actionName) && is_array($settings[$controllerName][$actionName])) {
					$settings = $settings[$controllerName][$actionName];
				} else {
					$settings = $settings[$controllerName];
				}
			}
			// SK: TODO: Look at this in detail
			// if (!empty($controllerName) && is_array($settings[$controllerName])) {
			// 	foreach ($settings[$controllerName] as $key => $value) {
			// 		if (array_key_exists($key, $settings)) {
			// 			$settings[$key] = $value;
			// 		}
			// 	}
			// }
			// if (!empty($actionName) && is_array($settings[$controllerName][$actionName])) {
			// 	foreach ($settings[$controllerName][$actionName] as $key => $value) {
			// 		if (array_key_exists($key, $settings)) {
			// 			$settings[$key] = $value;
			// 		}
			// 		if (array_key_exists($key, $settings[$controllerName])) {
			// 			$settings[$controllerName][$key] = $value;
			// 		}
			// 	}
			// }
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
	 * @param string $extensionKey
	 * @return void
	 * @see getSettings()
	 */
	public function loadGlobalSettings($extensionKey) {
		$settings = $this->settings[$extensionKey];
		if (empty($settings)) $settings = array();
		foreach ($this->configurationSources as $configurationSource) {
			$settings = t3lib_div::array_merge_recursive_overrule($settings, $configurationSource->load($extensionKey));
		}
		// $this->postProcessSettings($settings);
		$this->settings[$extensionKey] = $settings;
	}

}
?>