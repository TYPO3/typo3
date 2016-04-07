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
 * Class that implements Blowfish salted hashing based on PHP's
 * crypt() function.
 *
 * Warning: Blowfish salted hashing with PHP's crypt() is not available
 * on every system.
 */
class BlowfishSalt extends Md5Salt
{
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
     * Keeps length of a Blowfish salt in bytes.
     *
     * @var int
     */
    protected static $saltLengthBlowfish = 16;

    /**
     * Setting string to indicate type of hashing method (blowfish).
     *
     * @var string
     */
    protected static $settingBlowfish = '$2a$';

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
     * @return int Used hashcount for given hash string
     */
    protected function getCountLog2($setting)
    {
        $countLog2 = null;
        $setting = substr($setting, strlen($this->getSetting()));
        $firstSplitPos = strpos($setting, '$');
        // Hashcount existing
        if ($firstSplitPos !== false && $firstSplitPos <= 2 && is_numeric(substr($setting, 0, $firstSplitPos))) {
            $countLog2 = (int)substr($setting, 0, $firstSplitPos);
        }
        return $countLog2;
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
     * Returns whether all prerequisites for the hashing methods are matched
     *
     * @return bool Method available
     */
    public function isAvailable()
    {
        return CRYPT_BLOWFISH;
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
     * Overwrites {@link Md5Salt::getSaltLength()}
     * with Blowfish specifics.
     *
     * @return int Length of a Blowfish salt in bytes
     */
    public function getSaltLength()
    {
        return self::$saltLengthBlowfish;
    }

    /**
     * Returns setting string of Blowfish salted hashes.
     *
     * Overwrites {@link Md5Salt::getSetting()}
     * with Blowfish specifics.
     *
     * @return string Setting string of Blowfish salted hashes
     */
    public function getSetting()
    {
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
     * @return bool TRUE if salted hash needs an update, otherwise FALSE
     */
    public function isHashUpdateNeeded($saltedPW)
    {
        // Check whether this was an updated password.
        if (strncmp($saltedPW, '$2', 2) || !$this->isValidSalt($saltedPW)) {
            return true;
        }
        // Check whether the iteration count used differs from the standard number.
        $countLog2 = $this->getCountLog2($saltedPW);
        return !is_null($countLog2) && $countLog2 < $this->getHashCount();
    }

    /**
     * Method determines if a given string is a valid salt.
     *
     * Overwrites {@link Md5Salt::isValidSalt()} with
     * Blowfish specifics.
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
                    $salt = substr($salt, strrpos($salt, '$') + 1);
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
