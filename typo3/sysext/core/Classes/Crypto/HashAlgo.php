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

/**
 * Enum to be used for hashing functions.
 */
enum HashAlgo: string
{
    // SHA1 is still acceptable for HMAC, but not recommended
    case SHA1 = 'sha1';
    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';
    // preferring Keccak SHA3 over SHA2
    case SHA3_256 = 'sha3-256';
    case SHA3_384 = 'sha3-384';
    case SHA3_512 = 'sha3-512';

    private const ALLOWED_HMAC_ALGOS = [
        self::SHA1,
        self::SHA256,
        self::SHA384,
        self::SHA512,
        self::SHA3_256,
        self::SHA3_384,
        self::SHA3_512,
    ];

    private const BINARY_LENGTHS = [
        self::SHA1->value => 20,
        self::SHA256->value => 32,
        self::SHA384->value => 48,
        self::SHA512->value => 64,
        self::SHA3_256->value => 32,
        self::SHA3_384->value => 48,
        self::SHA3_512->value => 64,
    ];

    public function isAllowedForHmac(): bool
    {
        return in_array($this, self::ALLOWED_HMAC_ALGOS, true);
    }

    /**
     * @param bool $binary whether to return binary or hex length
     */
    public function length(bool $binary = false): int
    {
        return self::BINARY_LENGTHS[$this->value] * ($binary ? 1 : 2);
    }

    public function equals(string $other): bool
    {
        return strtolower($this->value) === strtolower($other);
    }

    public function hash(string $data, bool $binary = false): string
    {
        return hash($this->value, $data, $binary);
    }
}
