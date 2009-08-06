<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * @package Extbase
 * @subpackage Configuration\Source
 * @version $ID:$
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
		// TODO Needs a FE (does actually not work with BE or CLI)
		$settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_' . strtolower($extensionName) . '.']['settings.'];
		if (is_array($settings)) {
			$settings = Tx_Extbase_Configuration_Manager::postProcessSettings($settings);
		} else {
			$settings = array();
		}
		return $settings;
	}

}
?>