<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Crypto;

use TYPO3\CMS\Core\Exception\Crypto\InvalidHashStringException;

/**
 * A hash service to generate and validate SHA-1 hashes.
 */
final readonly class HashService
{
    /**
     * Returns a proper HMAC with a length of 40 (HMAC-SHA-1) on a given input string, additional secret
     * and the secret TYPO3 encryption key.
     *
     * @param non-empty-string $additionalSecret
     */
    public function hmac(string $input, string $additionalSecret): string
    {
        if ($additionalSecret === '') {
            throw new \LogicException('The ' . __METHOD__ . ' function requires a non-empty additional secret.', 1704453167);
        }
        $secret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . $additionalSecret;
        return hash_hmac('sha1', $input, $secret);
    }

    /**
     * Appends a hash (HMAC) to a given string and additional secret and returns the result
     *
     * @param non-empty-string $additionalSecret
     */
    public function appendHmac(string $string, string $additionalSecret): string
    {
        return $string . $this->hmac($string, $additionalSecret);
    }

    /**
     * Returns, if a string $string and $additionalSecret matches the HMAC given by $hash.
     *
     * @param non-empty-string $additionalSecret
     */
    public function validateHmac(string $string, string $additionalSecret, string $hmac): bool
    {
        return hash_equals($this->hmac($string, $additionalSecret), $hmac);
    }

    /**
     * Tests if the last 40 characters of a given string $string and $additionalSecret matches the HMAC of
     * the rest of the string and, if true, returns the string without the HMAC. In case of an invalid HMAC string
     * an exception is thrown.
     *
     * @param non-empty-string $string
     * @param non-empty-string $additionalSecret
     */
    public function validateAndStripHmac(string $string, string $additionalSecret): string
    {
        if (strlen($string) < 40) {
            throw new InvalidHashStringException('A hashed string must contain at least 40 characters, the given string was only ' . strlen($string) . ' characters long.', 1704454152);
        }
        $stringWithoutHmac = substr($string, 0, -40);
        if ($this->validateHmac($stringWithoutHmac, $additionalSecret, substr($string, -40)) !== true) {
            throw new InvalidHashStringException('The given string was not appended with a valid HMAC.', 1704454157);
        }
        return $stringWithoutHmac;
    }
}
