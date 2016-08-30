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

use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * This class contains an OpenSSL backend for the TYPO3 RSA authentication
 * service. It uses shell version of OpenSSL to perform tasks. See class
 * \TYPO3\CMS\Rsaauth\Backend\AbstractBackend for the information on using backends.
 */
class CommandLineBackend extends AbstractBackend
{
    /**
     * @var int
     */
    const DEFAULT_EXPONENT = 65537;

    /**
     * A path to the openssl binary or FALSE if the binary does not exist
     *
     * @var string|bool
     */
    protected $opensslPath;

    /**
     * Temporary directory. It is best of it is outside of the web site root and
     * not publicly readable.
     * For now we use typo3temp/.
     *
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * Creates an instance of this class. It obtains a path to the OpenSSL
     * binary.
     */
    public function __construct()
    {
        $this->opensslPath = CommandUtility::getCommand('openssl');
        $this->temporaryDirectory = PATH_site . 'typo3temp';
        // Get temporary directory from the configuration
        $extconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rsaauth']);
        if (
            $extconf['temporaryDirectory'] !== ''
            && $extconf['temporaryDirectory'][0] === '/'
            && @is_dir($extconf['temporaryDirectory'])
            && is_writable($extconf['temporaryDirectory'])
        ) {
            $this->temporaryDirectory = $extconf['temporaryDirectory'];
        }
    }

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
        $keyPair = GeneralUtility::makeInstance(\TYPO3\CMS\Rsaauth\Keypair::class);
        if ($keyPair->isReady()) {
            return $keyPair;
        }

        if ($this->opensslPath === false) {
            return null;
        }

        // Create a temporary file. Security: tempnam() sets permissions to 0600
        $privateKeyFile = tempnam($this->temporaryDirectory, StringUtility::getUniqueId());

        // Generate the private key.
        //
        // PHP generates 1024 bit key files. We force command line version
        // to do the same and use the F4 (0x10001) exponent. This is the most
        // secure.
        $command = $this->opensslPath . ' genrsa -out ' . escapeshellarg($privateKeyFile) . ' 1024';
        if (TYPO3_OS === 'WIN') {
            $command .= ' 2>NUL';
        } else {
            $command .= ' 2>/dev/null';
        }
        CommandUtility::exec($command);
        // Test that we got a private key
        $privateKey = file_get_contents($privateKeyFile);
        if (false !== strpos($privateKey, 'BEGIN RSA PRIVATE KEY')) {
            // Ok, we got the private key. Get the modulus.
            $command = $this->opensslPath . ' rsa -noout -modulus -in ' . escapeshellarg($privateKeyFile);
            $value = CommandUtility::exec($command);
            if (substr($value, 0, 8) === 'Modulus=') {
                $publicKey = substr($value, 8);

                $keyPair->setExponent(self::DEFAULT_EXPONENT);
                $keyPair->setPrivateKey($privateKey);
                $keyPair->setPublicKey($publicKey);
            }
        } else {
            $keyPair = null;
        }

        @unlink($privateKeyFile);
        return $keyPair;
    }

    /**
     * @param string $privateKey The private key (obtained from a call to createNewKeyPair())
     * @param string $data Data to decrypt (base64-encoded)
     * @return string Decrypted data or NULL in case of an error
     * @see \TYPO3\CMS\Rsaauth\Backend\AbstractBackend::decrypt()
     */
    public function decrypt($privateKey, $data)
    {
        // Key must be put to the file
        $privateKeyFile = tempnam($this->temporaryDirectory, StringUtility::getUniqueId());
        file_put_contents($privateKeyFile, $privateKey);
        $dataFile = tempnam($this->temporaryDirectory, StringUtility::getUniqueId());
        file_put_contents($dataFile, base64_decode($data));
        // Prepare the command
        $command = $this->opensslPath . ' rsautl -inkey ' . escapeshellarg($privateKeyFile) . ' -in ' . escapeshellarg($dataFile) . ' -decrypt';
        // Execute the command and capture the result
        $output = [];
        CommandUtility::exec($command, $output);
        // Remove the file
        @unlink($privateKeyFile);
        @unlink($dataFile);
        return implode(LF, $output);
    }

    /**
     * Checks if command line version of the OpenSSL is available and can be
     * executed successfully.
     *
     * @return bool
     * @see \TYPO3\CMS\Rsaauth\Backend\AbstractBackend::isAvailable()
     */
    public function isAvailable()
    {
        $result = false;
        if ($this->opensslPath) {
            // If path exists, test that command runs and can produce output
            $test = CommandUtility::exec($this->opensslPath . ' version');
            $result = substr($test, 0, 8) === 'OpenSSL ';
        }
        return $result;
    }
}
