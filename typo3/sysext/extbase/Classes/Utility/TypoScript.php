<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christian Müller <christian@kitsunet.de>
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
 * @api
 */
class Tx_Extbase_Utility_TypoScript {

	/**
	 * Removes all trailing dots recursively from TS settings array
	 *
	 * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
	 * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
	 *
	 * @param array $settings The settings array
	 * @return void
	 * @api
	 */
	static public function convertTypoScriptArrayToPlainArray(array $settings) {
		foreach ($settings as $key => &$value) {
			if(substr($key, -1) === '.') {
				$keyWithoutDot = substr($key, 0, -1);
				$hasNodeWithoutDot = array_key_exists($keyWithoutDot, $settings);
				$typoScriptNodeValue = $hasNodeWithoutDot ? $settings[$keyWithoutDot] : NULL;
				if(is_array($value)) {
					$settings[$keyWithoutDot] = self::convertTypoScriptArrayToPlainArray($value);
					if(!is_null($typoScriptNodeValue)) {
						$settings[$keyWithoutDot]['_typoScriptNodeValue']  = $typoScriptNodeValue;
					}
					unset($settings[$key]);
				} else {
					$settings[$keyWithoutDot] = NULL;
				}
			}
		}
		return $settings;
	}

	/**
	 * Returns an array with Typoscript the old way (with dot).
	 *
	 * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
	 * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
	 * However, if you want to call legacy TypoScript objects, you somehow need the "old" syntax (because this is what TYPO3 is used to).
	 * With this method, you can convert the extbase TypoScript to classical TYPO3 TypoScript which is understood by the rest of TYPO3.
	 *
	 * @param array $plainArray An Typoscript Array with Extbase Syntax (without dot but with _typoScriptNodeValue)
	 * @return array array with Typoscript as usual (with dot)
	 * @api
	 */
	static public function convertPlainArrayToTypoScriptArray($plainArray) {
		$typoScriptArray = array();
		if (is_array($plainArray)) {
			foreach ($plainArray as $key => $value) {
				if (is_array($value)) {
					if (isset($value['_typoScriptNodeValue'])) {
						$typoScriptArray[$key] = $value['_typoScriptNodeValue'];
						unset($value['_typoScriptNodeValue']);
					}
					$typoScriptArray[$key.'.'] = self::convertPlainArrayToTypoScriptArray($value);
				} else {
					$typoScriptArray[$key] = $value;
				}
			}
		}
		return $typoScriptArray;
	}
}
?>