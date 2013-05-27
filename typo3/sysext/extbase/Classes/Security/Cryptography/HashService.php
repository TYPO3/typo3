<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
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
 * A hash service which should be used to generate and validate hashes.
 *
 * It will use some salt / encryption key in the future.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Tx_Extbase_Security_Cryptography_HashService implements t3lib_singleton {

	/**
	 * Generate a hash for a given string
	 *
	 * @param string $string The string for which a hash should be generated
	 * @return string The hash of the string
	 * @throws F3\FLOW3\Security\Exception\InvalidArgumentForHashGeneration if something else than a string was given as parameter
	 * @todo Mark as API once it is more stable
	 */
	public function generateHash($string) {
		if (!is_string($string)) throw new Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration('A hash can only be generated for a string, but "' . gettype($string) . '" was given.', 1255069587);
		$encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		if (!$encryptionKey) throw new Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration('Encryption Key was empty!', 1255069597);
		return hash_hmac('sha1', $string, $encryptionKey);
	}

	/**
	 * Appends a hash (HMAC) to a given string and returns the result
	 *
	 * @param string $string The string for which a hash should be generated
	 * @return string The original string with HMAC of the string appended
	 * @see generateHmac()
	 * @todo Mark as API once it is more stable
	 */
	public function appendHmac($string) {
		$hmac = $this->generateHash($string);
		return $string . $hmac;
	}

	/**
	 * Test if a string $string has the hash given by $hash.
	 *
	 * @param string $string The string which should be validated
	 * @param string $hash The hash of the string
	 * @return boolean TRUE if string and hash fit together, FALSE otherwise.
	 * @todo Mark as API once it is more stable
	 */
	public function validateHash($string, $hash) {
		return ($this->generateHash($string) === $hash);
	}

	/**
	 * Tests if the last 40 characters of a given string $string
	 * matches the HMAC of the rest of the string and, if true,
	 * returns the string without the HMAC. In case of a HMAC
	 * validation error, an exception is thrown.
	 *
	 * @param string $string The string with the HMAC appended (in the format 'string<HMAC>')
	 * @return string the original string without the HMAC, if validation was successful
	 * @see validateHash()
	 * @throws Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration if the given string is not well-formatted
	 * @throws Tx_Extbase_Security_Exception_InvalidHash if the hash did not fit to the data.
	 * @todo Mark as API once it is more stable
	 */
	public function validateAndStripHmac($string) {
		if (!is_string($string)) {
			throw new Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration('A hash can only be validated for a string, but "' . gettype($string) . '" was given.', 1320829762);
		}
		if (strlen($string) < 40) {
			throw new Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration('A hashed string must contain at least 40 characters, the given string was only ' . strlen($string) . ' characters long.', 1320830276);
		}
		$stringWithoutHmac = substr($string, 0, -40);
		if ($this->validateHash($stringWithoutHmac, substr($string, -40)) !== TRUE) {
			throw new Tx_Extbase_Security_Exception_InvalidHash('The given string was not appended with a valid HMAC.', 1320830018);
		}
		return $stringWithoutHmac;
	}
}
?>