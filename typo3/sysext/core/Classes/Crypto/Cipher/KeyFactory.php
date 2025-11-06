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

namespace TYPO3\CMS\Core\Crypto\Cipher;

final readonly class KeyFactory
{
    /**
     * Derives a 32-byte key, based on the existing encryptionKey.
     * The key is supposed to be used in a symmetric XChaCha20-Poly1305 ciphering.
     *
     * @param string $seed (non-secret) value, used to build an 8-byte context (e.g. classname)
     * @param int $subKeyId variation to the resulting derived key (value from 0 to PHP_INT_MAX)
     * @throws CipherException
     * @throws \SodiumException
     */
    public function deriveSharedKeyFromEncryptionKey(string $seed, int $subKeyId = 1): SharedKey
    {
        $key = $this->adjustKeyLength($this->resolveEncryptionKey());
        // context must be exactly 8 bytes
        $context = hash('xxh64', $seed, true);
        return new SharedKey(
            sodium_crypto_kdf_derive_from_key(
                SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                $subKeyId,
                $context,
                $key
            )
        );
    }

    /**
     * Creates a SharedKey instance from a given key.
     *
     * @throws CipherException
     */
    public function createSharedKeyFromString(#[\SensitiveParameter] string $key): SharedKey
    {
        return new SharedKey($this->adjustKeyLength($key));
    }

    /**
     * Generates a SharedKey instance from a random key.
     *
     * @throws CipherException
     * @throws \Random\RandomException
     */
    public function generateSharedKey(): SharedKey
    {
        return new SharedKey(random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES));
    }

    /**
     * Ensures to use a 32-byte key for XChaCha20-Poly1305 encryption
     * (having a length of `SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES`).
     */
    private function adjustKeyLength(#[\SensitiveParameter] $key): string
    {
        if (strlen($key) === SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            return $key;
        }
        return hash('sha3-256', $key, true);
    }

    private function resolveEncryptionKey(): string
    {
        $key = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? null;
        if (!is_string($key) || $key === '') {
            throw new CipherException('No encryption key configured', 1762897148);
        }
        return $key;
    }
}
