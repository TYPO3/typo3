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

/**
 * Configuration source based on PHP files
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Configuration_Source_PHP implements TX_EXTMVC_Configuration_SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $extensionKey The extension key
	 * @return array
	 */
	public function load($extensionKey) {
		$pathAndFilename = t3lib_extMgm::extPath(strtolower($extensionKey)) . '/Configuration/Settings');
		$c = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Container');
		if (file_exists($pathAndFilename . '.php')) {
			require ($pathAndFilename . '.php');
		}
		return $c->getAsArray();
	}
}
?>