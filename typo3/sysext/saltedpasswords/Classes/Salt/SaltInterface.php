<?php
namespace TYPO3\CMS\Saltedpasswords\Salt;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Marcus Krause <marcus#exp2009@t3sec.info>
 *  (c) 2009-2013 Steffen Ritter <info@rs-websystems.de>
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
 * Contains interface "tx_saltedpasswords_salts" to be used in
 * classes that provide salted hashing.
 */
/**
 * Interface with public methods needed to be implemented
 * in a salting hashing class.
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @author Steffen Ritter <info@rs-websystems.de>
 * @since 2009-09-06
 */
interface SaltInterface {
	/**
	 * Method checks if a given plaintext password is correct by comparing it with
	 * a given salted hashed password.
	 *
	 * @param string $plainPW plain-text password to compare with salted hash
	 * @param string $saltedHashPW Salted hash to compare plain-text password with
	 * @return boolean TRUE, if plaintext password is correct, otherwise FALSE
	 */
	public function checkPassword($plainPW, $saltedHashPW);

	/**
	 * Returns length of required salt.
	 *
	 * @return integer Length of required salt
	 */
	public function getSaltLength();

	/**
	 * Returns wether all prequesites for the hashing methods are matched
	 *
	 * @return boolean Method available
	 */
	public function isAvailable();

	/**
	 * Method creates a salted hash for a given plaintext password
	 *
	 * @param string $password Plaintext password to create a salted hash from
	 * @param string $salt Optional custom salt to use
	 * @return string Salted hashed password
	 */
	public function getHashedPassword($password, $salt = NULL);

	/**
	 * Checks whether a user's hashed password needs to be replaced with a new hash.
	 *
	 * This is typically called during the login process when the plain text
	 * password is available.  A new hash is needed when the desired iteration
	 * count has changed through a change in the variable $hashCount or
	 * HASH_COUNT or if the user's password hash was generated in an bulk update
	 * with class ext_update.
	 *
	 * @param string $passString Salted hash to check if it needs an update
	 * @return boolean TRUE if salted hash needs an update, otherwise FALSE
	 */
	public function isHashUpdateNeeded($passString);

	/**
	 * Method determines if a given string is a valid salt
	 *
	 * @param string $salt String to check
	 * @return boolean TRUE if it's valid salt, otherwise FALSE
	 */
	public function isValidSalt($salt);

	/**
	 * Method determines if a given string is a valid salted hashed password.
	 *
	 * @param string $saltedPW String to check
	 * @return boolean TRUE if it's valid salted hashed password, otherwise FALSE
	 */
	public function isValidSaltedPW($saltedPW);

}

?>