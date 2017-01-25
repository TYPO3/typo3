<?php
namespace TYPO3\CMS\Saltedpasswords\Salt;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class that implements PHPass salted hashing based on Drupal's
 * modified Openwall implementation.
 *
 * Derived from Drupal CMS
 * original license: GNU General Public License (GPL)
 *
 * PHPass should work on every system.
 * @see http://drupal.org/node/29706/
 * @see http://www.openwall.com/phpass/
 */
class PhpassSalt extends AbstractSalt implements SaltInterface
{
    /**
     * Keeps a string for mapping an int to the corresponding
     * base 64 character.
     */
    const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * The default log2 number of iterations for password stretching.
     */
    const HASH_COUNT = 14;

    /**
     * The default maximum allowed log2 number of iterations for
     * password stretching.
     */
    const MAX_HASH_COUNT = 24;

    /**
     * The default minimum allowed log2 number of iterations for
     * password stretching.
     */
    const MIN_HASH_COUNT = 7;

    /**
     * Keeps log2 number
     * of iterations for password stretching.
     *
     * @var int
     */
    protected static $hashCount;

    /**
     * Keeps maximum allowed log2 number
     * of iterations for password stretching.
     *
     * @var int
     */
    protected static $maxHashCount;

    /**
     * Keeps minimum allowed log2 number
     * of iterations for password stretching.
     *
     * @var int
     */
    protected static $minHashCount;

    /**
     * Keeps length of a PHPass salt in bytes.
     *
     * @var int
     */
    protected static $saltLengthPhpass = 6;

    /**
     * Setting string to indicate type of hashing method (PHPass).
     *
     * @var string
     */
    protected static $settingPhpass = '$P$';

    /**
     * Method applies settings (prefix, hash count) to a salt.
     *
     * Overwrites {@link Md5Salt::applySettingsToSalt()}
     * with Blowfish specifics.
     *
     * @param string $salt A salt to apply setting to
     * @return string Salt with setting
     */
    protected function applySettingsToSalt($salt)
    {
        $saltWithSettings = $salt;
        $reqLenBase64 = $this->getLengthBase64FromBytes($this->getSaltLength());
        // Salt without setting
        if (strlen($salt) == $reqLenBase64) {
            // We encode the final log2 iteration count in base 64.
            $itoa64 = $this->getItoa64();
            $saltWithSettings = $this->getSetting() . $itoa64[$this->getHashCount()];
            $saltWithSettings .= $salt;
        }
        return $saltWithSettings;
    }

    /**
     * Method checks if a given plaintext password is correct by comparing it with
     * a given salted hashed password.
     *
     * @param string $plainPW Plain-text password to compare with salted hash
     * @param string $saltedHashPW Salted hash to compare plain-text password with
     * @return bool TRUE, if plain-text password matches the salted hash, otherwise FALSE
     */
    public function checkPassword($plainPW, $saltedHashPW)
    {
        $hash = $this->cryptPassword($plainPW, $saltedHashPW);
        return $hash && $saltedHashPW === $hash;
    }

    /**
     * Returns whether all prerequisites for the hashing methods are matched
     *
     * @return bool Method available
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * Hashes a password using a secure stretched hash.
     *
     * By using a salt and repeated hashing the password is "stretched". Its
     * security is increased because it becomes much more computationally costly
     * for an attacker to try to break the hash by brute-force computation of the
     * hashes of a large number of plain-text words or strings to find a match.
     *
     * @param string $password Plain-text password to hash
     * @param string $setting An existing hash or the output of getGeneratedSalt()
     * @return mixed A string containing the hashed password (and salt)
     */
    protected function cryptPassword($password, $setting)
    {
        $saltedPW = null;
        $reqLenBase64 = $this->getLengthBase64FromBytes($this->getSaltLength());
        // Retrieving settings with salt
        $setting = substr($setting, 0, strlen($this->getSetting()) + 1 + $reqLenBase64);
        $count_log2 = $this->getCountLog2($setting);
        // Hashes may be imported from elsewhere, so we allow != HASH_COUNT
        if ($count_log2 >= $this->getMinHashCount() && $count_log2 <= $this->getMaxHashCount()) {
            $salt = substr($setting, strlen($this->getSetting()) + 1, $reqLenBase64);
            // We must use md5() or sha1() here since they are the only cryptographic
            // primitives always available in PHP 5. To implement our own low-level
            // cryptographic function in PHP would result in much worse performance and
            // consequently in lower iteration counts and hashes that are quicker to crack
            // (by non-PHP code).
            $count = 1 << $count_log2;
            $hash = md5($salt . $password, true);
            do {
                $hash = md5($hash . $password, true);
            } while (--$count);
            $saltedPW = $setting . $this->base64Encode($hash, 16);
            // base64Encode() of a 16 byte MD5 will always be 22 characters.
            return strlen($saltedPW) == 34 ? $saltedPW : false;
        }
        return $saltedPW;
    }

    /**
     * Parses the log2 iteration count from a stored hash or setting string.
     *
     * @param string $setting Complete hash or a hash's setting string or to get log2 iteration count from
     * @return int Used hashcount for given hash string
     */
    protected function getCountLog2($setting)
    {
        return strpos($this->getItoa64(), $setting[strlen($this->getSetting())]);
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
    protected function getGeneratedSalt()
    {
        $randomBytes = \TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes($this->getSaltLength());
        return $this->base64Encode($randomBytes, $this->getSaltLength());
    }

    /**
     * Method returns log2 number of iterations for password stretching.
     *
     * @return int log2 number of iterations for password stretching
     * @see HASH_COUNT
     * @see $hashCount
     * @see setHashCount()
     */
    public function getHashCount()
    {
        return isset(self::$hashCount) ? self::$hashCount : self::HASH_COUNT;
    }

    /**
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password Plaintext password to create a salted hash from
     * @param string $salt Optional custom salt with setting to use
     * @return string salted hashed password
     */
    public function getHashedPassword($password, $salt = null)
    {
        $saltedPW = null;
        if (!empty($password)) {
            if (empty($salt) || !$this->isValidSalt($salt)) {
                $salt = $this->getGeneratedSalt();
            }
            $saltedPW = $this->cryptPassword($password, $this->applySettingsToSalt($salt));
        }
        return $saltedPW;
    }

    /**
     * Returns a string for mapping an int to the corresponding base 64 character.
     *
     * @return string String for mapping an int to the corresponding base 64 character
     */
    protected function getItoa64()
    {
        return self::ITOA64;
    }

    /**
     * Method returns maximum allowed log2 number of iterations for password stretching.
     *
     * @return int Maximum allowed log2 number of iterations for password stretching
     * @see MAX_HASH_COUNT
     * @see $maxHashCount
     * @see setMaxHashCount()
     */
    public function getMaxHashCount()
    {
        return isset(self::$maxHashCount) ? self::$maxHashCount : self::MAX_HASH_COUNT;
    }

    /**
     * Method returns minimum allowed log2 number of iterations for password stretching.
     *
     * @return int Minimum allowed log2 number of iterations for password stretching
     * @see MIN_HASH_COUNT
     * @see $minHashCount
     * @see setMinHashCount()
     */
    public function getMinHashCount()
    {
        return isset(self::$minHashCount) ? self::$minHashCount : self::MIN_HASH_COUNT;
    }

    /**
     * Returns length of a Blowfish salt in bytes.
     *
     * @return int Length of a Blowfish salt in bytes
     */
    public function getSaltLength()
    {
        return self::$saltLengthPhpass;
    }

    /**
     * Returns setting string of PHPass salted hashes.
     *
     * @return string Setting string of PHPass salted hashes
     */
    public function getSetting()
    {
        return self::$settingPhpass;
    }

    /**
     * Checks whether a user's hashed password needs to be replaced with a new hash.
     *
     * This is typically called during the login process when the plain text
     * password is available. A new hash is needed when the desired iteration
     * count has changed through a change in the variable $hashCount or
     * HASH_COUNT or if the user's password hash was generated in an bulk update
     * with class ext_update.
     *
     * @param string $passString Salted hash to check if it needs an update
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded($passString)
    {
        // Check whether this was an updated password.
        if (strncmp($passString, '$P$', 3) || strlen($passString) != 34) {
            return true;
        }
        // Check whether the iteration count used differs from the standard number.
        return $this->getCountLog2($passString) < $this->getHashCount();
    }

    /**
     * Method determines if a given string is a valid salt.
     *
     * @param string $salt String to check
     * @return bool TRUE if it's valid salt, otherwise FALSE
     */
    public function isValidSalt($salt)
    {
        $isValid = ($skip = false);
        $reqLenBase64 = $this->getLengthBase64FromBytes($this->getSaltLength());
        if (strlen($salt) >= $reqLenBase64) {
            // Salt with prefixed setting
            if (!strncmp('$', $salt, 1)) {
                if (!strncmp($this->getSetting(), $salt, strlen($this->getSetting()))) {
                    $isValid = true;
                    $salt = substr($salt, strrpos($salt, '$') + 2);
                } else {
                    $skip = true;
                }
            }
            // Checking base64 characters
            if (!$skip && strlen($salt) >= $reqLenBase64) {
                if (preg_match('/^[' . preg_quote($this->getItoa64(), '/') . ']{' . $reqLenBase64 . ',' . $reqLenBase64 . '}$/', substr($salt, 0, $reqLenBase64))) {
                    $isValid = true;
                }
            }
        }
        return $isValid;
    }

    /**
     * Method determines if a given string is a valid salted hashed password.
     *
     * @param string $saltedPW String to check
     * @return bool TRUE if it's valid salted hashed password, otherwise FALSE
     */
    public function isValidSaltedPW($saltedPW)
    {
        $isValid = false;
        $isValid = !strncmp($this->getSetting(), $saltedPW, strlen($this->getSetting()));
        if ($isValid) {
            $isValid = $this->isValidSalt($saltedPW);
        }
        return $isValid;
    }

    /**
     * Method sets log2 number of iterations for password stretching.
     *
     * @param int $hashCount log2 number of iterations for password stretching to set
     * @see HASH_COUNT
     * @see $hashCount
     * @see getHashCount()
     */
    public function setHashCount($hashCount = null)
    {
        self::$hashCount = !is_null($hashCount) && is_int($hashCount) && $hashCount >= $this->getMinHashCount() && $hashCount <= $this->getMaxHashCount() ? $hashCount : self::HASH_COUNT;
    }

    /**
     * Method sets maximum allowed log2 number of iterations for password stretching.
     *
     * @param int $maxHashCount Maximum allowed log2 number of iterations for password stretching to set
     * @see MAX_HASH_COUNT
     * @see $maxHashCount
     * @see getMaxHashCount()
     */
    public function setMaxHashCount($maxHashCount = null)
    {
        self::$maxHashCount = !is_null($maxHashCount) && is_int($maxHashCount) ? $maxHashCount : self::MAX_HASH_COUNT;
    }

    /**
     * Method sets minimum allowed log2 number of iterations for password stretching.
     *
     * @param int $minHashCount Minimum allowed log2 number of iterations for password stretching to set
     * @see MIN_HASH_COUNT
     * @see $minHashCount
     * @see getMinHashCount()
     */
    public function setMinHashCount($minHashCount = null)
    {
        self::$minHashCount = !is_null($minHashCount) && is_int($minHashCount) ? $minHashCount : self::MIN_HASH_COUNT;
    }
}
