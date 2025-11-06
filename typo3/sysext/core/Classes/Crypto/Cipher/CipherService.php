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

/**
 * Provides encryption and decryption based on XChaCha20-Poly1305.
 */
final readonly class CipherService
{
    /**
     * Encrypts the provided plain text using a shared key and additional authenticated data.
     *
     * @param string $plainText The plain text to be encrypted.
     * @param SharedKey $key The shared key used for encryption.
     * @param string $additionalData Optional additional authenticated data that will be included in the encryption.
     */
    public function encrypt(string $plainText, SharedKey $key, string $additionalData = ''): CipherValue
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plainText,
            $additionalData,
            $nonce,
            $key->value
        );
        return new CipherValue($nonce, $cipher);
    }

    /**
     * Decrypts the provided cipher value using a shared key and optional additional authenticated data.
     *
     * @param CipherValue $cipherValue The cipher value containing encrypted data and nonce.
     * @param SharedKey $key The shared key used for decryption.
     * @param string $additionalData Optional additional authenticated data that was included during encryption.
     * @throws CipherDecryptionFailedException If decryption fails or the integrity check is invalid.
     */
    public function decrypt(CipherValue $cipherValue, SharedKey $key, string $additionalData = ''): string
    {
        $result = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $cipherValue->cipher,
            $additionalData,
            $cipherValue->nonce,
            $key->value
        );
        if ($result === false) {
            throw new CipherDecryptionFailedException('Cipher could not be decrypted', 1762465681);
        }
        return $result;
    }
}
