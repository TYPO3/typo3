<?php
namespace TYPO3\CMS\Saltedpasswords\Salt;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Marcus Krause <marcus#exp2009@t3sec.info>
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
 */
/**
 * Abtract class with methods needed to be extended
 * in a salted hashing class.
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @since 2009-09-06
 */
abstract class AbstractSalt {

	/**
	 * Method applies settings (prefix, optional hash count, optional suffix)
	 * to a salt.
	 *
	 * @param string $salt A salt to apply setting to
	 * @return string Salt with setting
	 */
	abstract protected function applySettingsToSalt($salt);

	/**
	 * Generates a random base salt settings for the hash.
	 *
	 * @return string A string containing settings and a random salt
	 */
	abstract protected function getGeneratedSalt();

	/**
	 * Returns a string for mapping an int to the corresponding base 64 character.
	 *
	 * @return string String for mapping an int to the corresponding base 64 character
	 */
	abstract protected function getItoa64();

	/**
	 * Returns setting string to indicate type of hashing method.
	 *
	 * @return string Setting string of hashing method
	 */
	abstract protected function getSetting();

	/**
	 * Encodes bytes into printable base 64 using the *nix standard from crypt().
	 *
	 * @param string $input The string containing bytes to encode.
	 * @param integer $count The number of characters (bytes) to encode.
	 * @return string Encoded string
	 */
	public function base64Encode($input, $count) {
		$output = '';
		$i = 0;
		$itoa64 = $this->getItoa64();
		do {
			$value = ord($input[$i++]);
			$output .= $itoa64[$value & 63];
			if ($i < $count) {
				$value |= ord($input[$i]) << 8;
			}
			$output .= $itoa64[$value >> 6 & 63];
			if ($i++ >= $count) {
				break;
			}
			if ($i < $count) {
				$value |= ord($input[$i]) << 16;
			}
			$output .= $itoa64[$value >> 12 & 63];
			if ($i++ >= $count) {
				break;
			}
			$output .= $itoa64[$value >> 18 & 63];
		} while ($i < $count);
		return $output;
	}

	/**
	 * Method determines required length of base64 characters for a given
	 * length of a byte string.
	 *
	 * @param integer $byteLength Length of bytes to calculate in base64 chars
	 * @return integer Required length of base64 characters
	 */
	protected function getLengthBase64FromBytes($byteLength) {
		// Calculates bytes in bits in base64
		return intval(ceil($byteLength * 8 / 6));
	}

}


?>