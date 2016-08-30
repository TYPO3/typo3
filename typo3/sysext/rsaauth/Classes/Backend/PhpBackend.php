<?php
namespace TYPO3\CMS\Rsaauth\Backend;

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
 * This class contains a PHP OpenSSL backend for the TYPO3 RSA authentication
 * service. See class \TYPO3\CMS\Rsaauth\Backend\AbstractBackend for the information on using
 * backends.
 */
class PhpBackend extends AbstractBackend
{
    /**
     * Creates a new key pair for the encryption or gets the existing key pair (if one already has been generated).
     *
     * There should only be one key pair per request because the second private key would overwrites the first private
     * key. So the submitting the form with the first public key would not work anymore.
     *
     * @return \TYPO3\CMS\Rsaauth\Keypair|NULL a key pair or NULL in case of error
     */
    public function createNewKeyPair()
    {
        /** @var $keyPair \TYPO3\CMS\Rsaauth\Keypair */
        $keyPair = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Rsaauth\Keypair::class);
        if ($keyPair->isReady()) {
            return $keyPair;
        }

        $privateKey = @openssl_pkey_new();
        if ($privateKey !== false) {
            // Create private key as string
            $privateKeyStr = '';
            openssl_pkey_export($privateKey, $privateKeyStr);
            // Prepare public key information
            $exportedData = '';
            $csr = openssl_csr_new([
                'localityName' => 'foo',
                'organizationName' => 'bar',
            ], $privateKey);
            openssl_csr_export($csr, $exportedData, false);
            // Get public key (in fact modulus) and exponent
            $publicKey = $this->extractPublicKeyModulus($exportedData);
            $exponent = $this->extractExponent($exportedData);

            $keyPair->setExponent($exponent);
            $keyPair->setPrivateKey($privateKeyStr);
            $keyPair->setPublicKey($publicKey);
            // Clean up all resources
            openssl_free_key($privateKey);
        } else {
            $keyPair = null;
        }

        return $keyPair;
    }

    /**
     * Decrypts data using the private key. This implementation uses PHP OpenSSL
     * extension.
     *
     * @param string $privateKey The private key (obtained from a call to createNewKeyPair())
     * @param string $data Data to decrypt (base64-encoded)
     * @return string|NULL Decrypted data or NULL in case of an error
     * @see \TYPO3\CMS\Rsaauth\Backend\AbstractBackend::decrypt()
     */
    public function decrypt($privateKey, $data)
    {
        $result = '';
        if (!@openssl_private_decrypt(base64_decode($data), $result, $privateKey)) {
            $result = null;
        }
        return $result;
    }

    /**
     * Checks if this backend is available for calling. In particular checks if
     * PHP OpenSSl extension is installed and functional.
     *
     * @return bool
     * @see \TYPO3\CMS\Rsaauth\Backend\AbstractBackend::isAvailable()
     */
    public function isAvailable()
    {
        $result = false;
        if (is_callable('openssl_pkey_new')) {
            // PHP extension has to be configured properly. It
            // can be installed and available but will not work unless
            // properly configured. So we check if it works.
            $testKey = @openssl_pkey_new();
            if (is_resource($testKey)) {
                openssl_free_key($testKey);
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Extracts the exponent from the OpenSSL CSR
     *
     * @param string $data The result of openssl_csr_export()
     * @return int The exponent as a number
     */
    protected function extractExponent($data)
    {
        $index = strpos($data, 'Exponent: ');
        // We do not check for '$index === FALSE' because the exponent is
        // always there!
        return (int)substr($data, $index + 10);
    }

    /**
     * Extracts public key modulus from the OpenSSL CSR.
     *
     * @param string $data The result of openssl_csr_export()
     * @return string Modulus as uppercase hex string
     */
    protected function extractPublicKeyModulus($data)
    {
        $fragment = preg_replace('/.*Modulus.*?\\n(.*)Exponent:.*/ms', '\\1', $data);
        $fragment = preg_replace('/[\\s\\n\\r:]/', '', $fragment);
        return trim(strtoupper(substr($fragment, 2)));
    }
}
