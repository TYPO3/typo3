<?php
declare(ENCODING = 'utf-8');

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
 * A collection of utilities for extensions
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_ExtensionUtility {
	
	/**
	 * Returns a given string with underscores as UpperCamelCase (not UTF8 safe)
	 *
	 * @param	string	String to be converted to camel case
	 * @return	string	UpperCamelCasedWord
	 */
	public static function underscoreToUpperCamelCase($string) {
		$upperCamelCase = (str_replace(' ', '', ucwords(preg_replace('![^A-Z^a-z^0-9]+!', ' ', strtolower($string)))));
		return $upperCamelCase;
	}
	
	/**
	 * Returns a given string with underscores as lowerCamelCase (not UTF8 safe)
	 *
	 * @param	string	String to be converted to camel case
	 * @return	string	UpperCamelCasedWord
	 */
	public static function underscoreToLowerCamelCase($string) {
		$upperCamelCase = (str_replace(' ', '', ucwords(preg_replace('![^A-Z^a-z^0-9]+!', ' ', strtolower($string)))));
		$lowerCamelCase = strtolower(substr($string,0,1) ) . substr($string,1);
		return $lowerCamelCase;
	}
	
	/**
	 * Sets the first char of a string to lowercase (not UTF8 safe)
	 *
	 * @param string $string 
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public static function lowercaseFirst($string) {
		return strtolower(substr($string,0,1) ) . substr($string,1);
	}
	
}
?>