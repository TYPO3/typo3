<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Connection;

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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * TER2 connection handling class for the TYPO3 Extension Manager.
 *
 * It contains methods for downloading and uploading extensions and related code
 * @internal This class is a specific TER implementation and is not part of the Public TYPO3 API.
 */
class TerUtility
{
    /**
     * @var string
     */
    public $wsdlUrl;

    /**
     * Fetches an extension from the given mirror
     *
     * @param string $extensionKey Extension Key
     * @param string $version Version to install
     * @param string $expectedMd5 Expected MD5 hash of extension file
     * @param string $mirrorUrl URL of mirror to use
     * @throws ExtensionManagerException
     * @return array T3X data
     */
    public function fetchExtension($extensionKey, $version, $expectedMd5, $mirrorUrl)
    {
        if (
            (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extensionmanager', 'offlineMode')
            || Environment::isComposerMode()
        ) {
            throw new ExtensionManagerException('Extension Manager is in offline mode. No TER connection available.', 1437078620);
        }
        $extensionPath = strtolower($extensionKey);
        $mirrorUrl .= $extensionPath[0] . '/' . $extensionPath[1] . '/' . $extensionPath . '_' . $version . '.t3x';
        $t3x = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($mirrorUrl);
        $md5 = md5($t3x);
        if ($t3x === false) {
            throw new ExtensionManagerException(sprintf('The T3X file "%s" could not be fetched. Possible reasons: network problems, allow_url_fopen is off, cURL is not enabled in Install Tool.', $mirrorUrl), 1334426097);
        }
        if ($md5 === $expectedMd5) {
            // Fetch and return:
            $extensionData = $this->decodeExchangeData($t3x);
        } else {
            throw new ExtensionManagerException('Error: MD5 hash of downloaded file not as expected: ' . $md5 . ' != ' . $expectedMd5, 1334426098);
        }
        return $extensionData;
    }

    /**
     * Decode server data
     * This is information like the extension list, extension
     * information etc., return data after uploads (new em_conf)
     * On success, returns an array with data array and stats
     * array as key 0 and 1.
     *
     * @param string $externalData Data stream from remove server
     * @throws ExtensionManagerException
     * @return array $externalData
     * @see fetchServerData(), processRepositoryReturnData()
     */
    public function decodeServerData($externalData)
    {
        $parts = explode(':', $externalData, 4);
        $dat = base64_decode($parts[2]);
        gzuncompress($dat);
        // compare hashes ignoring any leading whitespace. See bug #0000365.
        if (ltrim($parts[0]) == md5($dat)) {
            if ($parts[1] === 'gzcompress') {
                if (function_exists('gzuncompress')) {
                    $dat = gzuncompress($dat);
                } else {
                    throw new ExtensionManagerException('Decoding Error: No decompressor available for compressed content. gzuncompress() function is not available!', 1342859463);
                }
            }
            $listArr = unserialize($dat, ['allowed_classes' => false]);
            if (!is_array($listArr)) {
                throw new ExtensionManagerException('Error: Unserialized information was not an array - strange!', 1342859489);
            }
        } else {
            throw new ExtensionManagerException('Error: MD5 hashes in T3X data did not match!', 1342859505);
        }
        return $listArr;
    }

    /**
     * Decodes extension upload array.
     * This kind of data is when an extension is uploaded to TER
     *
     * @param string $stream Data stream
     * @throws ExtensionManagerException
     * @return array Array with result on success, otherwise an error string.
     */
    public function decodeExchangeData($stream)
    {
        $parts = explode(':', $stream, 3);
        if ($parts[1] === 'gzcompress') {
            if (function_exists('gzuncompress')) {
                $parts[2] = gzuncompress($parts[2]);
            } else {
                throw new ExtensionManagerException('Decoding Error: No decompressor available for compressed content. gzcompress()/gzuncompress() ' . 'functions are not available!', 1344761814);
            }
        }
        if (hash_equals($parts[0], md5($parts[2]))) {
            $output = unserialize($parts[2], ['allowed_classes' => false]);
            if (!is_array($output)) {
                throw new ExtensionManagerException('Error: Content could not be unserialized to an array. Strange (since MD5 hashes match!)', 1344761938);
            }
        } else {
            throw new ExtensionManagerException('Error: MD5 mismatch. Maybe the extension file was downloaded and saved as a text file by the ' . 'browser and thereby corrupted!? (Always select "All" filetype when saving extensions)', 1344761991);
        }
        return $output;
    }
}
