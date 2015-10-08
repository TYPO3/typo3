<?php
namespace TYPO3\CMS\Rsaauth\Hook;

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
use TYPO3\CMS\Rsaauth\RsaEncryptionDecoder;
use TYPO3\CMS\Rsaauth\RsaEncryptionEncoder;
use TYPO3\CMS\Setup\Controller\SetupModuleController;

/**
 * This class provides a hook to the login form to add extra javascript code
 * and supply a proper form tag.
 */
class UserSetupHook
{
    /**
     * @var RsaEncryptionDecoder
     */
    protected $rsaEncryptionDecoder = null;

    /**
     * Decrypt all password fields which were encrypted.
     *
     * @param array $parameters Parameters to the script
     */
    public function decryptPassword(array $parameters)
    {
        if ($this->isRsaAvailable()) {
            // Note: although $parameters is not passed by reference, the 'be_user_data' is a reference
            $parameters['be_user_data'] = $this->getRsaEncryptionDecoder()->decrypt($parameters['be_user_data']);
        }
    }

    /**
     * Includes rsa libraries
     *
     * @param array $parameters Parameters to the script
     * @param SetupModuleController $userSetupObject Calling object: user setup module
     * @return string
     */
    public function getLoginScripts(array $parameters, SetupModuleController $userSetupObject)
    {
        $rsaEncryptionEncoder = GeneralUtility::makeInstance(RsaEncryptionEncoder::class);
        $rsaEncryptionEncoder->enableRsaEncryption(true);
        return '';
    }

    /**
     * Rsa is available if loginSecurityLevel is set and rsa backend is working.
     *
     * @return bool
     */
    protected function isRsaAvailable()
    {
        return trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) === 'rsa' && $this->getRsaEncryptionDecoder()->isAvailable();
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
