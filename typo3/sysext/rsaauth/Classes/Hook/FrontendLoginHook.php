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
use TYPO3\CMS\Rsaauth\RsaEncryptionEncoder;

/**
 * This class contains a hook to implement RSA authentication for the TYPO3
 * Frontend. Warning: felogin must be USER_INT for this to work!
 */
class FrontendLoginHook
{
    /**
     * Hooks to the felogin extension to provide additional code for FE login
     *
     * @return array 0 => onSubmit function, 1 => extra fields and required files
     */
    public function loginFormHook()
    {
        /** @var RsaEncryptionEncoder $rsaEncryptionEncoder */
        $rsaEncryptionEncoder = GeneralUtility::makeInstance(RsaEncryptionEncoder::class);
        if ($rsaEncryptionEncoder->isAvailable()) {
            $rsaEncryptionEncoder->enableRsaEncryption();
        }

        return [0 => '', 1 => ''];
    }
}
