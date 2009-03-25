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
 * Configuration source based on PHP files
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_ExtBase_Configuration_Source_PhpSource implements Tx_ExtBase_Configuration_SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content as an
	 * array. If the file does not exist or could not be loaded, an empty
	 * array is returned
	 *
	 * @param string $extensionKey The extension key
	 * @return array
	 */
	public function load($extensionKey) {
		$pathAndFilename = t3lib_extMgm::extPath(strtolower($extensionKey)) . '/Configuration/Settings';
		$c = t3lib_div::makeInstance('Tx_ExtBase_Configuration_Container');
		if (file_exists($pathAndFilename . '.php')) {
			require ($pathAndFilename . '.php');
		}
		return $c->getAsArray();
	}
}
?>