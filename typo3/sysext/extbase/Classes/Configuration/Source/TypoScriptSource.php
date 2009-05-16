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
 * Configuration source based on TS settings
 *
 */
class Tx_Extbase_Configuration_Source_TypoScriptSource implements Tx_Extbase_Configuration_SourceInterface {

	/**
	 * Loads the specified TypoScript configuration file and returns its content in a
	 * configuration container. If the file does not exist or could not be loaded,
	 * the empty configuration container is returned.
	 *
	 * @param string $extensionName The extension name
	 * @return array The settings as array without trailing dots
	 */
	 public function load($extensionName) {
		$settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_' . strtolower($extensionName) . '.'];
		if (is_array($settings)) {
			$settings = $this->postProcessSettings($settings);
		} else {
			$settings = array();
		}
		return $settings;
	}
	
	/**
	 * Removes all trailing dots recursively from TS settings array
	 *
	 * @param array $setup The settings array
	 * @return void
	 */
	protected function postProcessSettings(array $settings) {
		$processedSettings = array();
		foreach ($settings as $key => $value) {
			if (substr($key, -1) === '.') {
				$keyWithoutDot = substr($key, 0, -1);
				$processedSettings[$keyWithoutDot] = $this->postProcessSettings($value);
				if (array_key_exists($keyWithoutDot, $settings)) {
					$processedSettings[$keyWithoutDot]['_typoScriptNodeValue'] = $settings[$keyWithoutDot];
					unset($settings[$keyWithoutDot]);
				}
			} else {
				$keyWithDot = $key . '.';
				if (array_key_exists($keyWithDot, $settings)) {
					$processedSettings[$key] = $this->postProcessSettings($settings[$keyWithDot]);
					$processedSettings[$key]['_typoScriptNodeValue'] = $value;
					unset($settings[$keyWithDot]);
				} else {
					$processedSettings[$key] = $value;
				}
			}
		}
		return $processedSettings;
	}
	
}
?>