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
 * Abstract class with methods needed to be extended
 * in a salted hashing class.
 */
abstract class AbstractSalt
{
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
     * @param int $count The number of characters (bytes) to encode.
     * @return string Encoded string
     */
    public function base64Encode($input, $count)
    {
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
     * @param int $byteLength Length of bytes to calculate in base64 chars
     * @return int Required length of base64 characters
     */
    protected function getLengthBase64FromBytes($byteLength)
    {
        // Calculates bytes in bits in base64
        return (int)ceil($byteLength * 8 / 6);
    }
}
