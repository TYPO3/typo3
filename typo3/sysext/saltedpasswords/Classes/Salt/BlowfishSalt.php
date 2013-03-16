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
 * Contains class "tx_saltedpasswords_salts_blowfish"
 * that provides Blowfish salted hashing.
 */
/**
 * Class that implements Blowfish salted hashing based on PHP's
 * crypt() function.
 *
 * Warning: Blowfish salted hashing with PHP's crypt() is not available
 * on every system.
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @since 2009-09-06
 */
class BlowfishSalt extends \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt {

	/**
	 * The default log2 number of iterations for password stretching.
	 */
	const HASH_COUNT = 7;
	/**
	 * The default maximum allowed log2 number of iterations for
	 * password stretching.
	 */
	const MAX_HASH_COUNT = 17;
	/**
	 * The default minimum allowed log2 number of iterations for
	 * password stretching.
	 */
	const MIN_HASH_COUNT = 4;
	/**
	 * Keeps log2 number
	 * of iterations for password stretching.
	 *
	 * @var integer
	 */
	static protected $hashCount;

	/**
	 * Keeps maximum allowed log2 number
	 * of iterations for password stretching.
	 *
	 * @var integer
	 */
	static protected $maxHashCount;

	/**
	 * Keeps minimum allowed log2 number
	 * of iterations for password stretching.
	 *
	 * @var integer
	 */
	static protected $minHashCount;

	/**
	 * Keeps length of a Blowfish salt in bytes.
	 *
	 * @var integer
	 */
	static protected $saltLengthBlowfish = 16;

	/**
	 * Setting string to indicate type of hashing method (blowfish).
	 *
	 * @var string
	 */
	static protected $settingBlowfish = '$2a$';

	/**
	 * Method applies settings (prefix, hash count) to a salt.
	 *
	 * Overwrites {@link tx_saltedpasswords_salts_md5::applySettingsToSalt()}
	 * with Blowfish specifics.
	 *
	 * @param string $salt A salt to apply setting to
	 * @return string Salt with setting
	 */
	protected function applySettingsToSalt($salt) {
		$saltWithSettings = $salt;
		$reqLenBase64 = $this->getLengthBase64FromBytes($this->getSaltLength());
		// salt without setting
		if (strlen($salt) == $reqLenBase64) {
			$saltWithSettings = $this->getSetting() . sprintf('%02u', $this->getHashCount()) . '$' . $salt;
		}
		return $saltWithSettings;
	}

	/**
	 * Parses the log2 iteration count from a stored hash or setting string.
	 *
	 * @param string $setting Complete hash or a hash's setting string or to get log2 iteration count from
	 * @return integer Used hashcount for given hash string
	 */
	protected function getCountLog2($setting) {
		$countLog2 = NULL;
		$setting = substr($setting, strlen($this->getSetting()));
		$firstSplitPos = strpos($setting, '$');
		// Hashcount existing
		if ($firstSplitPos !== FALSE && $firstSplitPos <= 2 && is_numeric(substr($setting, 0, $firstSplitPos))) {
			$countLog2 = intval(substr($setting, 0, $firstSplitPos));
		}
		return $countLog2;
	}

	/**
	 * Method returns log2 number of iterations for password stretching.
	 *
	 * @return integer log2 number of iterations for password stretching
	 * @see HASH_COUNT
	 * @see $hashCount
	 * @see setHashCount()
	 */
	public function getHashCount() {
		return isset(self::$hashCount) ? self::$hashCount : self::HASH_COUNT;
	}

	/**
	 * Method returns maximum allowed log2 number of iterations for password stretching.
	 *
	 * @return integer Maximum allowed log2 number of iterations for password stretching
	 * @see MAX_HASH_COUNT
	 * @see $maxHashCount
	 * @see setMaxHashCount()
	 */
	public function getMaxHashCount() {
		return isset(self::$maxHashCount) ? self::$maxHashCount : self::MAX_HASH_COUNT;
	}

	/**
	 * Returns wether all prequesites for the hashing methods are matched
	 *
	 * @return boolean Method available
	 */
	public function isAvailable() {
		return CRYPT_BLOWFISH;
	}

	/**
	 * Method returns minimum allowed log2 number of iterations for password stretching.
	 *
	 * @return integer Minimum allowed log2 number of iterations for password stretching
	 * @see MIN_HASH_COUNT
	 * @see $minHashCount
	 * @see setMinHashCount()
	 */
	public function getMinHashCount() {
		return isset(self::$minHashCount) ? self::$minHashCount : self::MIN_HASH_COUNT;
	}

	/**
	 * Returns length of a Blowfish salt in bytes.
	 *
	 * Overwrites {@link tx_saltedpasswords_salts_md5::getSaltLength()}
	 * with Blowfish specifics.
	 *
	 * @return integer Length of a Blowfish salt in bytes
	 */
	public function getSaltLength() {
		return self::$saltLengthBlowfish;
	}

	/**
	 * Returns setting string of Blowfish salted hashes.
	 *
	 * Overwrites {@link tx_saltedpasswords_salts_md5::getSetting()}
	 * with Blowfish specifics.
	 *
	 * @return string Setting string of Blowfish salted hashes
	 */
	public function getSetting() {
		return self::$settingBlowfish;
	}

	/**
	 * Checks whether a user's hashed password needs to be replaced with a new hash.
	 *
	 * This is typically called during the login process when the plain text
	 * password is available.  A new hash is needed when the desired iteration
	 * count has changed through a change in the variable $hashCount or
	 * HASH_COUNT.
	 *
	 * @param string $saltedPW Salted hash to check if it needs an update
	 * @return boolean TRUE if salted hash needs an update, otherwise FALSE
	 */
	public function isHashUpdateNeeded($saltedPW) {
		// Check whether this was an updated password.
		if (strncmp($saltedPW, '$2', 2) || !$this->isValidSalt($saltedPW)) {
			return TRUE;
		}
		// Check whether the iteration count used differs from the standard number.
		$countLog2 = $this->getCountLog2($saltedPW);
		return !is_NULL($countLog2) && $countLog2 < $this->getHashCount();
	}

	/**
	 * Method determines if a given string is a valid salt.
	 *
	 * Overwrites {@link tx_saltedpasswords_salts_md5::isValidSalt()} with
	 * Blowfish specifics.
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
					$salt = substr($salt, strrpos($salt, '$') + 1);
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

	/**
	 * Method sets log2 number of iterations for password stretching.
	 *
	 * @param integer $hashCount log2 number of iterations for password stretching to set
	 * @see HASH_COUNT
	 * @see $hashCount
	 * @see getHashCount()
	 */
	public function setHashCount($hashCount = NULL) {
		self::$hashCount = !is_NULL($hashCount) && is_int($hashCount) && $hashCount >= $this->getMinHashCount() && $hashCount <= $this->getMaxHashCount() ? $hashCount : self::HASH_COUNT;
	}

	/**
	 * Method sets maximum allowed log2 number of iterations for password stretching.
	 *
	 * @param integer $maxHashCount Maximum allowed log2 number of iterations for password stretching to set
	 * @see MAX_HASH_COUNT
	 * @see $maxHashCount
	 * @see getMaxHashCount()
	 */
	public function setMaxHashCount($maxHashCount = NULL) {
		self::$maxHashCount = !is_NULL($maxHashCount) && is_int($maxHashCount) ? $maxHashCount : self::MAX_HASH_COUNT;
	}

	/**
	 * Method sets minimum allowed log2 number of iterations for password stretching.
	 *
	 * @param integer $minHashCount Minimum allowed log2 number of iterations for password stretching to set
	 * @see MIN_HASH_COUNT
	 * @see $minHashCount
	 * @see getMinHashCount()
	 */
	public function setMinHashCount($minHashCount = NULL) {
		self::$minHashCount = !is_NULL($minHashCount) && is_int($minHashCount) ? $minHashCount : self::MIN_HASH_COUNT;
	}

}


?>