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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rsaauth\RsaEncryptionDecoder;

/**
 * Class that hooks into DataHandler and decrypts rsa encrypted data
 */
class DecryptionHook
{
    /**
     * @param array $incomingFieldArray
     * @param string $table
     * @param int $id
     * @param DataHandler $parentObject
     */
    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, $parentObject)
    {
        $serializedString = serialize($incomingFieldArray);
        if (strpos($serializedString, 'rsa:') === false) {
            return;
        }

        $rsaEncryptionDecoder = GeneralUtility::makeInstance(RsaEncryptionDecoder::class);
        $incomingFieldArray = $rsaEncryptionDecoder->decrypt($incomingFieldArray);
    }
}
