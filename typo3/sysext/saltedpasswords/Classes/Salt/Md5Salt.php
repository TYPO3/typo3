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
 * Contains class "tx_saltedpasswords_salts_md5"
 * that provides MD5 salted hashing.
 */
/**
 * Class that implements MD5 salted hashing based on PHP's
 * crypt() function.
 *
 * MD5 salted hashing with PHP's crypt() should be available
 * on most of the systems.
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @since 2009-09-06
 */
class Md5Salt extends \TYPO3\CMS\Saltedpasswords\Salt\AbstractSalt implements \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface {

	/**
	 * Keeps a string for mapping an int to the corresponding
	 * base 64 character.
	 */
	const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	/**
	 * Keeps length of a MD5 salt in bytes.
	 *
	 * @var integer
	 */
	static protected $saltLengthMD5 = 6;

	/**
	 * Keeps suffix to be appended to a salt.
	 *
	 * @var string
	 */
	static protected $saltSuffixMD5 = '$';

	/**
	 * Setting string to indicate type of hashing method (md5).
	 *
	 * @var string
	 */
	static protected $settingMD5 = '$1$';

	/**
	 * Method applies settings (prefix, suffix) to a salt.
	 *
	 * @param string $salt A salt to apply setting to
	 * @return string Salt with setting
	 */
	protected function applySettingsToSalt($salt) {
		$saltWithSettings = $salt;
		$reqLenBase64 = $this->getLengthBase64FromBytes($this->getSaltLength());
		// Salt without setting
		if (strlen($salt) == $reqLenBase64) {
			$saltWithSettings = $this->getSetting() . $salt . $this->getSaltSuffix();
		}
		return $saltWithSettings;
	}

	/**
	 * Method checks if a given plaintext password is correct by comparing it with
	 * a given salted hashed password.
	 *
	 * @param string $plainPW plain-text password to compare with salted hash
	 * @param string $saltedHashPW salted hash to compare plain-text password with
	 * @return boolean TRUE, if plain-text password matches the salted hash, otherwise FALSE
	 */
	public function checkPassword($plainPW, $saltedHashPW) {
		$isCorrect = FALSE;
		if ($this->isValidSalt($saltedHashPW)) {
			$isCorrect = crypt($plainPW, $saltedHashPW) == $saltedHashPW;
		}
		return $isCorrect;
	}

	/**
	 * Generates a random base 64-encoded salt prefixed and suffixed with settings for the hash.
	 *
	 * Proper use of salts may defeat a number of attacks, including:
	 * - The ability to try candidate passwords against multiple hashes at once.
	 * - The ability to use pre-hashed lists of candidate passwords.
	 * - The ability to determine whether two users have the same (or different)
	 * password without actually having to guess one of the passwords.
	 *
	 * @return string A character string containing settings and a random salt
	 */
	protected function getGeneratedSalt() {
		$randomBytes = \TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes($this->getSaltLength());
		return $this->base64Encode($randomBytes, $this->getSaltLength());
	}

	/**
	 * Method creates a salted hash for a given plaintext password
	 *
	 * @param string $password plaintext password to create a salted hash from
	 * @param string $salt Optional custom salt with setting to use
	 * @return string Salted hashed password
	 */
	public function getHashedPassword($password, $salt = NULL) {
		$saltedPW = NULL;
		if (!empty($password)) {
			if (empty($salt) || !$this->isValidSalt($salt)) {
				$salt = $this->getGeneratedSalt();
			}
			$saltedPW = crypt($password, $this->applySettingsToSalt($salt));
		}
		return $saltedPW;
	}

	/**
	 * Returns a string for mapping an int to the corresponding base 64 character.
	 *
	 * @return string String for mapping an int to the corresponding base 64 character
	 */
	protected function getItoa64() {
		return self::ITOA64;
	}

	/**
	 * Returns wether all prequesites for the hashing methods are matched
	 *
	 * @return boolean Method available
	 */
	public function isAvailable() {
		return CRYPT_MD5;
	}

	/**
	 * Returns length of a MD5 salt in bytes.
	 *
	 * @return integer Length of a MD5 salt in bytes
	 */
	public function getSaltLength() {
		return self::$saltLengthMD5;
	}

	/**
	 * Returns suffix to be appended to a salt.
	 *
	 * @return string Suffix of a salt
	 */
	protected function getSaltSuffix() {
		return self::$saltSuffixMD5;
	}

	/**
	 * Returns setting string of MD5 salted hashes.
	 *
	 * @return string Setting string of MD5 salted hashes
	 */
	public function getSetting() {
		return self::$settingMD5;
	}

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
	public function isHashUpdateNeeded($passString) {
		return FALSE;
	}

	/**
	 * Method determines if a given string is a valid salt
	 *
	 * @param string $salt String to check
	 * @return boolean TRUE if it's valid salt, otherwise FALSE
	 */
	public function isValidSalt($salt) {
		$isValid = ($skip = FALSE);
		$reqLenBase64 = $this->getLengthBase64FromBytes($this->getSaltLength());
		if (strlen($salt) >= $reqLenBase64) {
			// Salt with prefixed setting
			if (!strncmp('$', $salt, 1)) {
				if (!strncmp($this->getSetting(), $salt, strlen($this->getSetting()))) {
					$isValid = TRUE;
					$salt = substr($salt, strlen($this->getSetting()));
				} else {
					$skip = TRUE;
				}
			}
			// Checking base64 characters
			if (!$skip && strlen($salt) >= $reqLenBase64) {
				if (preg_match('/^[' . preg_quote($this->getItoa64(), '/') . ']{' . $reqLenBase64 . ',' . $reqLenBase64 . '}$/', substr($salt, 0, $reqLenBase64))) {
					$isValid = TRUE;
				}
			}
		}
		return $isValid;
	}

	/**
	 * Method determines if a given string is a valid salted hashed password.
	 *
	 * @param string $saltedPW String to check
	 * @return boolean TRUE if it's valid salted hashed password, otherwise FALSE
	 */
	public function isValidSaltedPW($saltedPW) {
		$isValid = FALSE;
		$isValid = !strncmp($this->getSetting(), $saltedPW, strlen($this->getSetting())) ? TRUE : FALSE;
		if ($isValid) {
			$isValid = $this->isValidSalt($saltedPW);
		}
		return $isValid;
	}

}


?>