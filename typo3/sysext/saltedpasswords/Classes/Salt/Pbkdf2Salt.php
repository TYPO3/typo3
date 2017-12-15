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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that implements PBKDF2 salted hashing based on PHP's
 * hash_pbkdf2() function.
 */
class Pbkdf2Salt extends AbstractSalt implements SaltInterface
{
    /**
     * Keeps a string for mapping an int to the corresponding
     * base 64 character.
     */
    const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * The default number of iterations for password stretching.
     */
    const HASH_COUNT = 25000;

    /**
     * The default maximum allowed number of iterations for password stretching.
     */
    const MAX_HASH_COUNT = 10000000;

    /**
     * The default minimum allowed number of iterations for password stretching.
     */
    const MIN_HASH_COUNT = 1000;

    /**
     * Keeps number of iterations for password stretching.
     *
     * @var int
     */
    protected static $hashCount;

    /**
     * Keeps maximum allowed number of iterations for password stretching.
     *
     * @var int
     */
    protected static $maxHashCount;

    /**
     * Keeps minimum allowed number of iterations for password stretching.
     *
     * @var int
     */
    protected static $minHashCount;

    /**
     * Keeps length of a PBKDF2 salt in bytes.
     *
     * @var int
     */
    protected static $saltLengthPbkdf2 = 16;

    /**
     * Setting string to indicate type of hashing method (PBKDF2).
     *
     * @var string
     */
    protected static $settingPbkdf2 = '$pbkdf2-sha256$';

    /**
     * Method applies settings (prefix, hash count) to a salt.
     *
     * Overwrites {@link Md5Salt::applySettingsToSalt()}
     * with PBKDF2 specifics.
     *
     * @param string $salt A salt to apply setting to
     * @return string Salt with setting
     */
    protected function applySettingsToSalt($salt)
    {
        $saltWithSettings = $salt;
        // salt without setting
        if (strlen($salt) === $this->getSaltLength()) {
            $saltWithSettings = $this->getSetting() . sprintf('%02u', $this->getHashCount()) . '$' . $this->base64Encode($salt, $this->getSaltLength());
        }
        return $saltWithSettings;
    }

    /**
     * Method checks if a given plaintext password is correct by comparing it with
     * a given salted hashed password.
     *
     * @param string $plainPW plain-text password to compare with salted hash
     * @param string $saltedHashPW salted hash to compare plain-text password with
     * @return bool TRUE, if plain-text password matches the salted hash, otherwise FALSE
     */
    public function checkPassword($plainPW, $saltedHashPW)
    {
        return $this->isValidSalt($saltedHashPW) && hash_equals($this->getHashedPassword($plainPW, $saltedHashPW), $saltedHashPW);
    }

    /**
     * Parses the log2 iteration count from a stored hash or setting string.
     *
     * @param string $setting Complete hash or a hash's setting string or to get log2 iteration count from
     * @return int|null Used hashcount for given hash string
     */
    protected function getIterationCount($setting)
    {
        $iterationCount = null;
        $setting = substr($setting, strlen($this->getSetting()));
        $firstSplitPos = strpos($setting, '$');
        // Hashcount existing
        if ($firstSplitPos !== false
            && $firstSplitPos <= strlen((string)$this->getMaxHashCount())
            && is_numeric(substr($setting, 0, $firstSplitPos))
        ) {
            $iterationCount = (int)substr($setting, 0, $firstSplitPos);
        }
        return $iterationCount;
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
        return GeneralUtility::makeInstance(Random::class)->generateRandomBytes($this->getSaltLength());
    }

    /**
     * Parses the salt out of a salt string including settings. If the salt does not include settings
     * it is returned unmodified.
     *
     * @param string $salt
     * @return string
     */
    protected function getStoredSalt($salt)
    {
        if (!strncmp('$', $salt, 1)) {
            if (!strncmp($this->getSetting(), $salt, strlen($this->getSetting()))) {
                $saltParts = GeneralUtility::trimExplode('$', $salt, 4);
                $salt = $saltParts[2];
            }
        }
        return $this->base64Decode($salt);
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
     * Method creates a salted hash for a given plaintext password
     *
     * @param string $password plaintext password to create a salted hash from
     * @param string $salt Optional custom salt with setting to use
     * @return string|null Salted hashed password
     */
    public function getHashedPassword($password, $salt = null)
    {
        $saltedPW = null;
        if ($password !== '') {
            if (empty($salt) || !$this->isValidSalt($salt)) {
                $salt = $this->getGeneratedSalt();
            } else {
                $this->setHashCount($this->getIterationCount($salt));
                $salt = $this->getStoredSalt($salt);
            }
            $hash = hash_pbkdf2('sha256', $password, $salt, $this->getHashCount(), 0, true);
            $saltedPW = $this->applySettingsToSalt($salt) . '$' . $this->base64Encode($hash, strlen($hash));
        }
        return $saltedPW;
    }

    /**
     * Method returns number of iterations for password stretching.
     *
     * @return int number of iterations for password stretching
     * @see HASH_COUNT
     * @see $hashCount
     * @see setHashCount()
     */
    public function getHashCount()
    {
        return isset(self::$hashCount) ? self::$hashCount : self::HASH_COUNT;
    }

    /**
     * Method returns maximum allowed number of iterations for password stretching.
     *
     * @return int Maximum allowed number of iterations for password stretching
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
        return function_exists('hash_pbkdf2');
    }

    /**
     * Method returns minimum allowed number of iterations for password stretching.
     *
     * @return int Minimum allowed number of iterations for password stretching
     * @see MIN_HASH_COUNT
     * @see $minHashCount
     * @see setMinHashCount()
     */
    public function getMinHashCount()
    {
        return isset(self::$minHashCount) ? self::$minHashCount : self::MIN_HASH_COUNT;
    }

    /**
     * Returns length of a PBKDF2 salt in bytes.
     *
     * Overwrites {@link Md5Salt::getSaltLength()}
     * with PBKDF2 specifics.
     *
     * @return int Length of a PBKDF2 salt in bytes
     */
    public function getSaltLength()
    {
        return self::$saltLengthPbkdf2;
    }

    /**
     * Returns setting string of PBKDF2 salted hashes.
     *
     * Overwrites {@link Md5Salt::getSetting()}
     * with PBKDF2 specifics.
     *
     * @return string Setting string of PBKDF2 salted hashes
     */
    public function getSetting()
    {
        return self::$settingPbkdf2;
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
        if (strncmp($saltedPW, $this->getSetting(), strlen($this->getSetting())) || !$this->isValidSalt($saltedPW)) {
            return true;
        }
        // Check whether the iteration count used differs from the standard number.
        $iterationCount = $this->getIterationCount($saltedPW);
        return !is_null($iterationCount) && $iterationCount < $this->getHashCount();
    }

    /**
     * Method determines if a given string is a valid salt.
     *
     * Overwrites {@link Md5Salt::isValidSalt()} with
     * PBKDF2 specifics.
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
        $isValid = !strncmp($this->getSetting(), $saltedPW, strlen($this->getSetting()));
        if ($isValid) {
            $isValid = $this->isValidSalt($saltedPW);
        }
        return $isValid;
    }

    /**
     * Method sets number of iterations for password stretching.
     *
     * @param int $hashCount number of iterations for password stretching to set
     * @see HASH_COUNT
     * @see $hashCount
     * @see getHashCount()
     */
    public function setHashCount($hashCount = null)
    {
        self::$hashCount = !is_null($hashCount) && is_int($hashCount) && $hashCount >= $this->getMinHashCount() && $hashCount <= $this->getMaxHashCount() ? $hashCount : self::HASH_COUNT;
    }

    /**
     * Method sets maximum allowed number of iterations for password stretching.
     *
     * @param int $maxHashCount Maximum allowed number of iterations for password stretching to set
     * @see MAX_HASH_COUNT
     * @see $maxHashCount
     * @see getMaxHashCount()
     */
    public function setMaxHashCount($maxHashCount = null)
    {
        self::$maxHashCount = !is_null($maxHashCount) && is_int($maxHashCount) ? $maxHashCount : self::MAX_HASH_COUNT;
    }

    /**
     * Method sets minimum allowed number of iterations for password stretching.
     *
     * @param int $minHashCount Minimum allowed number of iterations for password stretching to set
     * @see MIN_HASH_COUNT
     * @see $minHashCount
     * @see getMinHashCount()
     */
    public function setMinHashCount($minHashCount = null)
    {
        self::$minHashCount = !is_null($minHashCount) && is_int($minHashCount) ? $minHashCount : self::MIN_HASH_COUNT;
    }

    /**
     * Adapted version of base64_encoding for compatibility with python passlib. The output of this function is
     * is identical to base64_encode, except that it uses . instead of +, and omits trailing padding = and whitepsace.
     *
     * @param string $input The string containing bytes to encode.
     * @param int $count The number of characters (bytes) to encode.
     * @return string Encoded string
     */
    public function base64Encode($input, $count)
    {
        $input = substr($input, 0, $count);
        return rtrim(str_replace('+', '.', base64_encode($input)), " =\r\n\t\0\x0B");
    }

    /**
     * Adapted version of base64_encoding for compatibility with python passlib. The output of this function is
     * is identical to base64_encode, except that it uses . instead of +, and omits trailing padding = and whitepsace.
     *
     * @param string $value
     * @return string
     */
    public function base64Decode($value)
    {
        return base64_decode(str_replace('.', '+', $value));
    }
}
