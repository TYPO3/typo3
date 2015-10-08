<?php
namespace TYPO3\CMS\Jumpurl;

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
 * This class contains functions for generating and validating jump URLs
 */
class JumpUrlUtility
{
    /**
     * Calculates the hash for the given jump URL
     *
     * @param string $jumpUrl The target URL
     * @return string The calculated hash
     */
    public static function calculateHash($jumpUrl)
    {
        return GeneralUtility::hmac($jumpUrl, 'jumpurl');
    }

    /**
     * Calculates the hash for the given jump URL secure data.
     *
     * @param string $jumpUrl The URL to the file
     * @param string $locationData Information about the record that rendered the jump URL, format is [pid]:[table]:[uid]
     * @param string $mimeType Mime type of the file or an empty string
     * @return string The calculated hash
     */
    public static function calculateHashSecure($jumpUrl, $locationData, $mimeType)
    {
        $data = array((string)$jumpUrl, (string)$locationData, (string)$mimeType);
        return GeneralUtility::hmac(serialize($data));
    }
}
