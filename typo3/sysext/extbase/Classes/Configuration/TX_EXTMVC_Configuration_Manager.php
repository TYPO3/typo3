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
	 * The configuration sources used for loading the raw configuration
	 *
	 * @var array
	 */
	protected $configurationSources;

	/**
	 * Constructs the configuration manager
	 *
	 * @param array $configurationSources An array of configuration sources
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
	public function getSettings($extensionKey) {
		if (isset($this->settings[$extensionKey])) {
			$settings = $this->settings[$extensionKey];
		} else {
			$settings = array();
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
		foreach ($this->configurationSources as $configurationSource) {
			$settings = t3lib_div::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_PACKAGES . $extensionKey . '/Configuration/Settings'));
		}
		$this->postProcessSettings($settings);
		$this->settings = t3lib_div::arrayMergeRecursiveOverrule($this->settings, $settings);
	}

	/**
	 * Post processes the given settings array by replacing constants with their
	 * actual value.
	 *
	 * This is a preliminary solution, we'll surely have some better way to handle
	 * this soon.
	 *
	 * @param array &$settings The settings to post process. The results are stored directly in the given array
	 * @return void
	 */
	protected function postProcessSettings(&$settings) {
		foreach ($settings as $key => $setting) {
			if (is_array($setting)) {
				$this->postProcessSettings($settings[$key]);
			} elseif (is_string($setting)) {
				$matches = array();
				preg_match_all('/(?:%)([a-zA-Z_0-9]+)(?:%)/', $setting, $matches);
				if (count($matches[1]) > 0) {
					foreach ($matches[1] as $match) {
						if (defined($match)) $settings[$key] = str_replace('%' . $match . '%', constant($match), $settings[$key]);
					}
				}
			}
		}
	}
}
?>