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

namespace TYPO3\CMS\Extbase\Security\Cryptography;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Crypto\HashService as CoreHashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;

/**
 * A hash service which should be used to generate and validate hashes.
 *
 * It will use some salt / encryption key in the future.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 *
 * @deprecated will be removed in TYPO3 v14.0. Use \TYPO3\CMS\Core\Crypto\HashService instead.
 *
 * Note: Remove used exception codes in AbstractExceptionHandler::IGNORED_HMAC_EXCEPTION_CODES in v14.0
 */
#[Autoconfigure(public: true)]
class HashService
{
    public function __construct(protected readonly CoreHashService $hashService) {}

    /**
     * Generate a hash (HMAC) for a given string
     *
     * @param string $string The string for which a hash should be generated
     * @return string The hash of the string
     */
    public function generateHmac(string $string): string
    {
        trigger_error(
            __CLASS__ . ' has been marked as deprecated in TYPO3 v13. Use \TYPO3\CMS\Core\Crypto\HashService instead.',
            E_USER_DEPRECATED,
        );
        return $this->hashService->hmac($string, self::class);
    }

    /**
     * Appends a hash (HMAC) to a given string and returns the result
     *
     * @param string $string The string for which a hash should be generated
     * @return string The original string with HMAC of the string appended
     * @see generateHmac()
     * @todo Mark as API once it is more stable
     */
    public function appendHmac(string $string): string
    {
        trigger_error(
            __CLASS__ . ' has been marked as deprecated in TYPO3 v13. Use \TYPO3\CMS\Core\Crypto\HashService instead.',
            E_USER_DEPRECATED,
        );
        return $this->hashService->appendHmac($string, self::class);
    }

    /**
     * Tests if a string $string matches the HMAC given by $hash.
     *
     * @param string $string The string which should be validated
     * @param string $hmac The hash of the string
     * @return bool TRUE if string and hash fit together, FALSE otherwise.
     */
    public function validateHmac(string $string, string $hmac): bool
    {
        trigger_error(
            __CLASS__ . ' has been marked as deprecated in TYPO3 v13. Use \TYPO3\CMS\Core\Crypto\HashService instead.',
            E_USER_DEPRECATED,
        );
        return $this->hashService->validateHmac($string, self::class, $hmac);
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
    public function validateAndStripHmac(string $string): string
    {
        trigger_error(
            __CLASS__ . ' has been marked as deprecated in TYPO3 v13. Use \TYPO3\CMS\Core\Crypto\HashService instead.',
            E_USER_DEPRECATED,
        );

        if (strlen($string) < 40) {
            throw new InvalidArgumentForHashGenerationException('A hashed string must contain at least 40 characters, the given string was only ' . strlen($string) . ' characters long.', 1320830276);
        }
        $stringWithoutHmac = substr($string, 0, -40);
        if ($this->validateHmac($stringWithoutHmac, substr($string, -40)) !== true) {
            throw new InvalidHashException('The given string was not appended with a valid HMAC.', 1320830018);
        }
        return $stringWithoutHmac;
    }
}
