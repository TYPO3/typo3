<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christian M�ller <christian@kitsunet.de>
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
 * Utilities to manage and convert Typoscript Code
 *
 * @package Extbase
 * @subpackage Utility
 * @version $ID:$
 */
class Tx_Extbase_Utility_TypoScript {

	/**
	 * Returns an array with Typoscript the old way (with dot).
	 *
	 * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
	 * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
	 * However, if you want to call legacy TypoScript objects, you somehow need the "old" syntax (because this is what TYPO3 is used to).
	 * With this method, you can convert the extbase TypoScript to classical TYPO3 TypoScript which is understood by the rest of TYPO3.
	 *
	 * @param array $extbaseTS An Typoscript Array with Extbase Syntax (without dot but with _typoscriptNodeValue)
	 * @return array array with Typoscript as usual (with dot)
	 * @api
	 */
	static public function convertExtbaseToClassicTS($extbaseTS) {
		$classicTS = array();
		if (is_array($extbaseTS)) {
			foreach ($extbaseTS as $key => $value) {
				if (is_array($value)) {
					if (isset($value['_typoscriptNodeValue'])) {
						$classicTS[$key] = $value['_typoscriptNodeValue'];
						unset($value['_typoscriptNodeValue']);
					}
					$classicTS[$key.'.'] = Tx_Extbase_Utility_TypoScript::convertExtbaseToClassicTS($value);
				} else {
					$classicTS[$key] = $value;
				}
			}
		}
		return $classicTS;
	}
}
?>