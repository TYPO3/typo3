<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Marcus Krause <marcus#exp2009@t3sec.info>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Contains abstract class "tx_saltedpasswords_abstract_salts"
 * to be used in classes that provide salted hashing.
 *
 * $Id$
 */


/**
 * Abtract class with methods needed to be extended
 * in a salted hashing class.
 *
 * @author      Marcus Krause <marcus#exp2009@t3sec.info>
 *
 * @abstract
 * @since   	2009-09-06
 * @package     TYPO3
 * @subpackage  tx_saltedpasswords
 */
abstract class tx_saltedpasswords_abstract_salts {
	/**
	 * Method applies settings (prefix, optional hash count, optional suffix)
	 * to a salt.
	 *
	 * @param	string		$salt:  a salt to apply setting to
	 * @return	string		salt with setting
	 */
	abstract protected function applySettingsToSalt($salt);

	/**
	 * Generates a random base salt settings for the hash.
	 *
	 * @return	string		a string containing settings and a random salt
	 */
	abstract protected function getGeneratedSalt();

	/**
	 * Returns a string for mapping an int to the corresponding base 64 character.
	 *
	 * @return	string		string for mapping an int to the corresponding base 64 character
	 */
	abstract protected function getItoa64();

	/**
	 * Returns setting string to indicate type of hashing method.
	 *
	 * @return	string		setting string of hashing method
	 */
	abstract protected function getSetting();

	/**
	 * Encodes bytes into printable base 64 using the *nix standard from crypt().
	 *
	 * @param	string		$input: the string containing bytes to encode.
	 * @param	integer		$count: the number of characters (bytes) to encode.
	 * @return	string		encoded string
	 */
	public function base64Encode($input, $count) {
		$output = '';
		$i = 0;
		$itoa64 = $this->getItoa64();
		do {
			$value = ord($input[$i++]);
			$output .= $itoa64[$value & 0x3f];
			if ($i < $count) {
				$value |= ord($input[$i]) << 8;
			}
			$output .= $itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count) {
				break;
			}
			if ($i < $count) {
				$value |= ord($input[$i]) << 16;
			}
			$output .= $itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count) {
				break;
			}
			$output .= $itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);
		return $output;
	}

	/**
	 * Method determines required length of base64 characters for a given
	 * length of a byte string.
	 *
	 * @param	integer		$byteLength: length of bytes to calculate in base64 chars
	 * @return	integer		required length of base64 characters
	 */
	protected function getLengthBase64FromBytes($byteLength) {
			// calculates bytes in bits in base64
		return intval(ceil(($byteLength * 8) / 6));
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/salts/class.tx_saltedpasswords_abstract_salts.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/salts/class.tx_saltedpasswords_abstract_salts.php']);
}
?>