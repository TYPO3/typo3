..  include:: /Includes.rst.txt

..  _feature-108002-1762466108:

====================================================================================
Feature: #108002 - Introduce built-in symmetric encryption/decryption cipher service
====================================================================================

See :issue:`108002`

Description
===========

TYPO3 now provides a built-in symmetric encryption and decryption service using the
modern XChaCha20-Poly1305 AEAD (Authenticated Encryption with Associated Data) cipher.
This service allows extensions and core code to securely encrypt sensitive data such as
API tokens, passwords, or personal information without implementing custom encryption
solutions or relying on third-party libraries.

Benefits
--------

The new :php:`\TYPO3\CMS\Core\Crypto\Cipher\CipherService` provides several advantages:

*   **Secure by default**: Uses modern, cryptographically secure algorithms provided by
    the libsodium library (`ext-sodium`), which is available by default in all PHP
    versions currently supported by TYPO3.
*   **Seamless integration**: Secret keys can be derived from TYPO3's existing
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']`, eliminating the need
    to manage additional encryption keys.
*   **Authenticated encryption**: The AEAD cipher not only encrypts data but also
    ensures its integrity, preventing tampering.
*   **Application-specific keys**: Different components can derive isolated encryption
    keys using seed values, enhancing security through key separation.

Key Derivation
--------------

Secret keys are derived from the existing TYPO3 encryption key using the
:php:`\TYPO3\CMS\Core\Crypto\Cipher\KeyFactory`. This approach leverages the
master encryption key that is already configured in your TYPO3 installation, ensuring
that different applications or contexts can have their own isolated keys while using
the same master key.

Please note that to decrypt data in the future, the encryptionKey must not be
changed and must remain available.

Example for deriving a secret key:

..  code-block:: php

    use TYPO3\CMS\Core\Crypto\Cipher\KeyFactory;

    // Get the KeyFactory via dependency injection
    public function __construct(
        private readonly KeyFactory $keyFactory
    ) {}

    // Derive an application-specific secret key from the TYPO3 encryption key
    // The seed 'my-extension-tokens' identifies this specific use case
    $secretKey = $this->keyFactory->deriveSharedKeyFromEncryptionKey('my-extension-tokens');

    // You can also derive multiple sub-keys from the same seed
    $secretKey1 = $this->keyFactory->deriveSharedKeyFromEncryptionKey('my-extension-tokens', 1);
    $secretKey2 = $this->keyFactory->deriveSharedKeyFromEncryptionKey('my-extension-tokens', 2);

Each unique seed produces a different encryption key, allowing for key separation
between different features or data types within your extension.

Key Usage/Generation
--------------------

Instead of deriving a key, it is also possible to generate a new key from a random
value or to use a provided key (e.g., via environment variables).

*   :php:`$this->keyFactory->createSharedKeyFromString(getenv('MY_APP_KEY'))`
*   :php:`$this->keyFactory->generateSharedKey()`

Encryption Example
------------------

To encrypt sensitive data, use the :php:`CipherService::encrypt()` method:

..  code-block:: php

    use TYPO3\CMS\Core\Crypto\Cipher\CipherService;
    use TYPO3\CMS\Core\Crypto\Cipher\KeyFactory;

    // Get services via dependency injection
    public function __construct(
        private readonly CipherService $cipherService,
        private readonly KeyFactory $keyFactory
    ) {}

    public function encryptApiToken(string $apiToken): string
    {
        // Derive a secret key for API token encryption
        $secretKey = $this->keyFactory->deriveSharedKeyFromEncryptionKey('api-tokens');

        // Encrypt the API token
        $cipherValue = $this->cipherService->encrypt($apiToken, $secretKey);

        // Convert to string for storage in database
        // The result has the format: {base64url-encoded-json}
        // Example: "eyJub25jZSI6IlxcXHUwMDEy4oCmIiwiY2lwaGVyIjoi4oCmIn0"
        // This string contains both the nonce and ciphertext in a JSON structure
        $encryptedString = (string)$cipherValue;

        return $encryptedString;
    }

Each encryption generates a unique result due to the random nonce, even when
encrypting the same plaintext multiple times.

Decryption Example
------------------

To decrypt data, use the :php:`CipherService::decrypt()` method with the same secret
key that was used for encryption:

..  code-block:: php

    use TYPO3\CMS\Core\Crypto\Cipher\CipherService;
    use TYPO3\CMS\Core\Crypto\Cipher\CipherValue;
    use TYPO3\CMS\Core\Crypto\Cipher\KeyFactory;
    use TYPO3\CMS\Core\Crypto\Cipher\CipherDecryptionFailedException;

    // Get services via dependency injection
    public function __construct(
        private readonly CipherService $cipherService,
        private readonly KeyFactory $keyFactory
    ) {}

    public function decryptApiToken(string $encryptedToken): string
    {
        // Derive the same secret key used during encryption
        $secretKey = $this->keyFactory->deriveSharedKeyFromEncryptionKey('api-tokens');

        // Parse the cipher text (format: {base64url-encoded-json})
        $cipherValue = CipherValue::fromSerialized($encryptedToken);

        try {
            // Decrypt the token
            $apiToken = $this->cipherService->decrypt($cipherValue, $secretKey);
            return $apiToken;
        } catch (CipherDecryptionFailedException $e) {
            // Decryption failed - wrong key, tampered data, or invalid format
            throw new \RuntimeException(
                'Failed to decrypt API token: ' . $e->getMessage(),
                1762465682,
                $e
            );
        }
    }

Decryption will fail if the wrong key is used, the data has been tampered with,
or the ciphertext format is invalid.

Impact
======

The :php:`CipherService` and :php:`KeyFactory` are now available as
autoconfigured services throughout TYPO3 core and extensions. This provides
a standardized, secure way to encrypt and decrypt sensitive data without
requiring additional dependencies beyond ext-sodium, which is already a
requirement of TYPO3 core.

Extensions can now securely store encrypted data in the database or configuration
files using this built-in service, ensuring consistent security practices across
the TYPO3 ecosystem.

..  index:: ext:core
