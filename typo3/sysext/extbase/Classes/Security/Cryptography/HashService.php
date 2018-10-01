<?php
namespace TYPO3\CMS\Extbase\Security\Cryptography;

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
 * A hash service which should be used to generate and validate hashes.
 *
 * It will use some salt / encryption key in the future.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class HashService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Generate a hash (HMAC) for a given string
     *
     * @param string $string The string for which a hash should be generated
     * @return string The hash of the string
     * @throws \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException if something else than a string was given as parameter
     */
    public function generateHmac($string)
    {
        if (!is_string($string)) {
            throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be generated for a string, but "' . gettype($string) . '" was given.', 1255069587);
        }
        $encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        if (!$encryptionKey) {
            throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('Encryption Key was empty!', 1255069597);
        }
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
    public function appendHmac($string)
    {
        $hmac = $this->generateHmac($string);
        return $string . $hmac;
    }

    /**
     * Tests if a string $string matches the HMAC given by $hash.
     *
     * @param string $string The string which should be validated
     * @param string $hmac The hash of the string
     * @return bool TRUE if string and hash fit together, FALSE otherwise.
     */
    public function validateHmac($string, $hmac)
    {
        return hash_equals($this->generateHmac($string), $hmac);
    }

    /**
     * Tests if the last 40 characters of a given string $string
     * matches the HMAC of the rest of the string and, if true,
     * returns the string without the HMAC. In case of a HMAC
     * validation error, an exception is thrown.
     *
     * @param string $string The string with the HMAC appended (in the format 'string<HMAC>')
     * @return string the original string without the HMAC, if validation was successful
     * @see validateHmac()
     * @throws \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException if the given string is not well-formatted
     * @throws \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException if the hash did not fit to the data.
     * @todo Mark as API once it is more stable
     */
    public function validateAndStripHmac($string)
    {
        if (!is_string($string)) {
            throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be validated for a string, but "' . gettype($string) . '" was given.', 1320829762);
        }
        if (strlen($string) < 40) {
            throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('A hashed string must contain at least 40 characters, the given string was only ' . strlen($string) . ' characters long.', 1320830276);
        }
        $stringWithoutHmac = substr($string, 0, -40);
        if ($this->validateHmac($stringWithoutHmac, substr($string, -40)) !== true) {
            throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException('The given string was not appended with a valid HMAC.', 1320830018);
        }
        return $stringWithoutHmac;
    }
}
