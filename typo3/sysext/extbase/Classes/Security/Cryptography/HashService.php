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
 * @version $Id: OpenSSLRSAKey.php 2813 2009-07-16 14:02:34Z k-fish $
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
	 * @todo encryption key has to come from somewhere else
	 */
	public function generateHash($string) {
		if (!is_string($string)) throw new Tx_Extbase_Security_Exception_InvalidArgumentForHashGeneration('A hash can only be generated for a string, but "' . gettype($string) . '" was given.', 1255069587);
		$encryptionKey = '7nN5#n8guP/oA9Bq95x=e/x}.hL[:7yv1BJcWrB0AYQ5WJ!KGd'; // TODO
		return sha1($string . $encryptionKey);
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
}
?>