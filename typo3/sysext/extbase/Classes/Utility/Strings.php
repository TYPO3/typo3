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
 * A collection of utilities for extensions
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
abstract class Tx_ExtBase_Utility_Strings {
	
	/**
	 * Returns a given string with underscores as UpperCamelCase (not UTF8 safe)
	 *
	 * @param string String to be converted to camel case
	 * @return string UpperCamelCasedWord
	 */
	public static function underscoredToUpperCamelCase($string) {
		$upperCamelCase = (str_replace(' ', '', ucwords(preg_replace('![^A-Z^a-z^0-9]+!', ' ', strtolower($string)))));
		return $upperCamelCase;
	}
	
	/**
	 * Returns a given string with underscores as lowerCamelCase (not UTF8 safe)
	 *
	 * @param string String to be converted to camel case
	 * @return string lowerCamelCasedWord
	 */
	public static function underscoredToLowerCamelCase($string) {
		$upperCamelCase = (str_replace(' ', '', ucwords(preg_replace('![^A-Z^a-z^0-9]+!', ' ', strtolower($string)))));
		$lowerCamelCase = strtolower(substr($upperCamelCase,0,1) ) . substr($upperCamelCase,1);
		return $lowerCamelCase;
	}
	
	/**
	 * Returns a given CamelCasedString as an lowercase string with underscores (not UTF8 safe)
	 *
	 * @param string String to be converted to lowercase underscore
	 * @return string lowercase_and_underscored_string
	 */
	public static function camelCaseToLowerCaseUnderscored($string) {
		return strtolower(preg_replace('/(?<=\w)([A-Z])/', '_\\1', $string));
	}
		
	/**
	 * Sets the first char of a string to lowercase (not UTF8 safe)
	 *
	 * @param string $string 
	 * @return void
	 */
	public static function lowercaseFirst($string) {
		return strtolower(substr($string,0,1) ) . substr($string,1);
	}
	
	/**
	 * Returns the extension key. Automatically detects the extension key from the classname.
	 *
	 * @return string The extension key
	 */
	public static function getExtensionKey() {
		if(preg_match('/^Tx_([^_]+)/', get_class($this), $matches)) {
			$possibleExtensionKey = $matches[1];
			if($possibleExtensionKey != 'lib') {
				$loadedExtensionKeys = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']);
				foreach($loadedExtensionKeys as $extensionKey) {
					if($possibleExtensionKey == str_replace('_', '', $extensionKey)) {
						return $extensionKey;
					}
				}
			}
		}
	}
	
}
?>