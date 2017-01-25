<?php
namespace TYPO3\CMS\Rsaauth;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service "RSA authentication" for the "rsaauth" extension. This service will
 * authenticate a user using hos password encoded with one time public key. It
 * uses the standard TYPO3 service to do all dirty work. Firsts, it will decode
 * the password and then pass it to the parent service ('sv'). This ensures that it
 * always works, even if other TYPO3 internals change.
 */
class RsaAuthService extends \TYPO3\CMS\Sv\AuthenticationService
{
    /**
     * @var RsaEncryptionDecoder
     */
    protected $rsaEncryptionDecoder = null;

    /**
     * Standard extension key for the service
     * The extension key.
     *
     * @var string
     */
    public $extKey = 'rsaauth';

    /**
     * Standard prefix id for the service
     * Same as class name
     *
     * @var string
     */
    public $prefixId = 'tx_rsaauth_sv1';

    /**
     * Standard relative path for the service
     * Path to this script relative to the extension dir.
     *
     * @var string
     */
    public $scriptRelPath = 'Classes/RsaAuthService.php';

    /**
     * Process the submitted credentials.
     * In this case decrypt the password if it is RSA encrypted.
     *
     * @param array $loginData Credentials that are submitted and potentially modified by other services
     * @param string $passwordTransmissionStrategy Keyword of how the password has been hashed or encrypted before submission
     * @return bool
     */
    public function processLoginData(array &$loginData, $passwordTransmissionStrategy)
    {
        $isProcessed = false;
        if ($passwordTransmissionStrategy === 'rsa') {
            $password = $loginData['uident'];
            if (substr($password, 0, 4) === 'rsa:') {
                $decryptedPassword = $this->getRsaEncryptionDecoder()->decrypt($password);
                if ($decryptedPassword !== $password) {
                    $loginData['uident_text'] = $decryptedPassword;
                    $isProcessed = true;
                } else {
                    if ($this->pObj->writeDevLog) {
                        GeneralUtility::devLog('Process login data: Failed to RSA decrypt password', self::class);
                    }
                }
            } else {
                if ($this->pObj->writeDevLog) {
                    GeneralUtility::devLog('Process login data: passwordTransmissionStrategy has been set to "rsa" but no rsa encrypted password has been found.', self::class);
                }
            }
        }
        return $isProcessed;
    }

    /**
     * Initializes the service.
     *
     * @return bool
     */
    public function init()
    {
        return parent::init() && $this->getRsaEncryptionDecoder()->isAvailable();
    }

    /**
     * @return RsaEncryptionDecoder
     */
    protected function getRsaEncryptionDecoder()
    {
        if ($this->rsaEncryptionDecoder === null) {
            $this->rsaEncryptionDecoder = GeneralUtility::makeInstance(RsaEncryptionDecoder::class);
        }

        return $this->rsaEncryptionDecoder;
    }
}
