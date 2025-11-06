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

namespace TYPO3\CMS\Core\Tests\Functional\Crypto\Cipher;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\Cipher\CipherException;
use TYPO3\CMS\Core\Crypto\Cipher\CipherService;
use TYPO3\CMS\Core\Crypto\Cipher\CipherValue;
use TYPO3\CMS\Core\Crypto\Cipher\KeyFactory;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CipherServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            // key is shorter than required `SODIUM_CRYPTO_KDF_KEYBYTES`
            'encryptionKey' => 'short-test-key',
        ],
    ];

    #[Test]
    public function encryptAndDecryptRoundTripUsingDerivedSharedKey(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = 'This is a secret message';

        $cipherValue = $cipherService->encrypt($plainText, $key);
        $decrypted = $cipherService->decrypt($cipherValue, $key);

        self::assertSame($plainText, $decrypted);
        self::assertNotSame($plainText, $cipherValue->cipher);
    }

    #[Test]
    public function encryptAndDecryptRoundTripUsingProvidedSharedKey(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->createSharedKeyFromString('test');
        $plainText = 'This is a secret message';

        $cipherValue = $cipherService->encrypt($plainText, $key);
        $decrypted = $cipherService->decrypt($cipherValue, $key);

        self::assertSame($plainText, $decrypted);
        self::assertNotSame($plainText, $cipherValue->cipher);
    }

    #[Test]
    public function encryptAndDecryptRoundTripUsingGeneratedSharedKey(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->generateSharedKey();
        $plainText = 'This is a secret message';

        $cipherValue = $cipherService->encrypt($plainText, $key);
        $decrypted = $cipherService->decrypt($cipherValue, $key);

        self::assertSame($plainText, $decrypted);
        self::assertNotSame($plainText, $cipherValue->cipher);
    }

    #[Test]
    public function encryptionProducesDifferentCiphertexts(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = 'This is a secret message';

        $cipherValue1 = $cipherService->encrypt($plainText, $key);
        $cipherValue2 = $cipherService->encrypt($plainText, $key);

        // Same plaintext encrypted twice should produce different ciphertexts due to random nonce
        self::assertNotSame($cipherValue1->cipher, $cipherValue2->cipher);
        self::assertNotSame($cipherValue1->nonce, $cipherValue2->nonce);

        // But both should decrypt to the same plaintext
        self::assertSame($plainText, $cipherService->decrypt($cipherValue1, $key));
        self::assertSame($plainText, $cipherService->decrypt($cipherValue2, $key));
    }

    #[Test]
    public function decryptionWithCorrectKeySucceeds(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = 'Sensitive data';

        $cipherValue = $cipherService->encrypt($plainText, $key);
        $decrypted = $cipherService->decrypt($cipherValue, $key);

        self::assertSame($plainText, $decrypted);
    }

    #[Test]
    public function decryptionWithWrongKeyFails(): void
    {
        $cipherService = $this->createCipherService();
        $key1 = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test1');
        $key2 = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test2');
        $plainText = 'Secret data';

        $cipherValue = $cipherService->encrypt($plainText, $key1);

        $this->expectException(CipherException::class);
        $this->expectExceptionCode(1762465681);
        $cipherService->decrypt($cipherValue, $key2);
    }

    #[Test]
    public function encryptAndDecryptWithAdditionalData(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = 'Authenticated message';
        $additionalData = 'user-id:123';

        $cipherValue = $cipherService->encrypt($plainText, $key, $additionalData);
        $decrypted = $cipherService->decrypt($cipherValue, $key, $additionalData);

        self::assertSame($plainText, $decrypted);
    }

    #[Test]
    public function decryptionWithWrongAdditionalDataFails(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = 'Authenticated message';
        $additionalData = 'user-id:123';

        $cipherValue = $cipherService->encrypt($plainText, $key, $additionalData);

        $this->expectException(CipherException::class);
        $this->expectExceptionCode(1762465681);
        $cipherService->decrypt($cipherValue, $key, 'user-id:456');
    }

    #[Test]
    public function decryptionWithMissingAdditionalDataFails(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = 'Authenticated message';
        $additionalData = 'user-id:123';

        $cipherValue = $cipherService->encrypt($plainText, $key, $additionalData);

        $this->expectException(CipherException::class);
        $this->expectExceptionCode(1762465681);
        $cipherService->decrypt($cipherValue, $key, '');
    }

    #[Test]
    public function cipherValueDeserialization(): void
    {
        // Create a properly formatted serialized cipher value for testing deserialization
        // Use a 24-character string for nonce (SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES = 24)
        $nonce = str_repeat('a', SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipher = 'test-cipher-text';

        // Create the serialized format manually using base64url encoding (URL-safe)
        $data = [
            'nonce' => StringUtility::base64urlEncode($nonce),
            'cipher' => StringUtility::base64urlEncode($cipher),
        ];
        $serialized = StringUtility::base64urlEncode(json_encode($data));

        $cipherValue = CipherValue::fromSerialized($serialized);
        self::assertSame($nonce, $cipherValue->nonce);
        self::assertSame($cipher, $cipherValue->cipher);

        $reserialized = (string)$cipherValue;
        self::assertSame($serialized, $reserialized);
    }

    #[Test]
    public function deserializeInvalidFormatThrowsException(): void
    {
        $this->expectException(CipherException::class);
        $this->expectExceptionCode(1762450821);
        CipherValue::fromSerialized('invalid:format');
    }

    #[Test]
    public function deserializeInvalidNonceLengthThrowsException(): void
    {
        // Create tampered data with an invalid nonce length
        $invalidNonce = 'short'; // Invalid - too short
        $cipher = 'some-cipher-data';
        $data = ['nonce' => $invalidNonce, 'cipher' => $cipher];
        $tamperedSerialized = StringUtility::base64urlEncode(json_encode($data));

        $this->expectException(CipherException::class);
        $this->expectExceptionCode(1762450477);
        CipherValue::fromSerialized($tamperedSerialized);
    }

    #[Test]
    public function encryptEmptyString(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = '';

        $cipherValue = $cipherService->encrypt($plainText, $key);
        $decrypted = $cipherService->decrypt($cipherValue, $key);

        self::assertSame('', $decrypted);
        self::assertNotEmpty($cipherValue->cipher);
    }

    #[Test]
    public function encryptUnicodeContent(): void
    {
        $cipherService = $this->createCipherService();
        $key = $this->createKeyFactory()->deriveSharedKeyFromEncryptionKey('test');
        $plainText = 'Hello 世界 🔒 Ñoño';

        $cipherValue = $cipherService->encrypt($plainText, $key);
        $decrypted = $cipherService->decrypt($cipherValue, $key);

        self::assertSame($plainText, $decrypted);
    }

    #[Test]
    public function encodingHandlesMalformedUtf8Characters(): void
    {
        // Binary data with invalid UTF-8 sequences that would fail in `json_encode` (9 bytes)
        $invalidUtf8Sequences = "\xFF\xFE\x80\x81\x82\x83\x00\xF0\x90";

        // Create a 24-byte nonce containing invalid UTF-8 sequences
        // (9 bytes + 3 * 5 bytes = 24 bytes <=> SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES)
        $nonce = $invalidUtf8Sequences . str_repeat("\xF0\x91\x92", 5);

        // Create cipher text with invalid UTF-8 sequences
        $cipher = $invalidUtf8Sequences . "some-encrypted-data\xFF\xFE";

        // Create CipherValue with the malformed UTF-8 binary data
        $cipherValue = new CipherValue($nonce, $cipher);

        $encoded = $cipherValue->encode();
        self::assertNotEmpty($encoded);

        $deserialized = CipherValue::fromSerialized($encoded);
        self::assertSame(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, strlen($deserialized->nonce));
        self::assertNotEmpty($deserialized->cipher);
    }

    private function createCipherService(): CipherService
    {
        return new CipherService();
    }

    private function createKeyFactory(): KeyFactory
    {
        return new KeyFactory();
    }
}
