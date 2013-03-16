<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Connection;

/* **************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2006-2013 Karsten Dambekalns <karsten@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * TER2 connection handling class for the TYPO3 Extension Manager.
 *
 * It contains methods for downloading and uploading extensions and related code
 *
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TerUtility {

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
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return array T3X data
	 */
	public function fetchExtension($extensionKey, $version, $expectedMd5, $mirrorUrl) {
		$extensionPath = \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($extensionKey);
		$mirrorUrl .= $extensionPath[0] . '/' . $extensionPath[1] . '/' . $extensionPath . '_' . $version . '.t3x';
		$t3x = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($mirrorUrl, 0, array(TYPO3_user_agent));
		$md5 = md5($t3x);
		if ($t3x === FALSE) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('The T3X file "%s" could not be fetched. Possible reasons: network problems, allow_url_fopen is off,' . ' cURL is not enabled in Install Tool.', $mirrorUrl), 1334426097);
		}
		if ($md5 == $expectedMd5) {
			// Fetch and return:
			$extensionData = $this->decodeExchangeData($t3x);
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Error: MD5 hash of downloaded file not as expected:<br />' . $md5 . ' != ' . $expectedMd5, 1334426098);
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
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return array $externalData
	 * @see fetchServerData(), processRepositoryReturnData()
	 */
	public function decodeServerData($externalData) {
		$parts = explode(':', $externalData, 4);
		$dat = base64_decode($parts[2]);
		gzuncompress($dat);
		// compare hashes ignoring any leading whitespace. See bug #0000365.
		if (ltrim($parts[0]) == md5($dat)) {
			if ($parts[1] == 'gzcompress') {
				if (function_exists('gzuncompress')) {
					$dat = gzuncompress($dat);
				} else {
					throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Decoding Error: No decompressor available for compressed content. gzuncompress() function is not available!', 1342859463);
				}
			}
			$listArr = unserialize($dat);
			if (!is_array($listArr)) {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Error: Unserialized information was not an array - strange!', 1342859489);
			}
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Error: MD5 hashes in T3X data did not match!', 1342859505);
		}
		return $listArr;
	}

	/**
	 * Decodes extension upload array.
	 * This kind of data is when an extension is uploaded to TER
	 *
	 * @param string $stream Data stream
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return array Array with result on success, otherwise an error string.
	 */
	public function decodeExchangeData($stream) {
		$parts = explode(':', $stream, 3);
		if ($parts[1] == 'gzcompress') {
			if (function_exists('gzuncompress')) {
				$parts[2] = gzuncompress($parts[2]);
			} else {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Decoding Error: No decompressor available for compressed content. gzcompress()/gzuncompress() ' . 'functions are not available!', 1344761814);
			}
		}
		if (md5($parts[2]) == $parts[0]) {
			$output = unserialize($parts[2]);
			if (!is_array($output)) {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Error: Content could not be unserialized to an array. Strange (since MD5 hashes match!)', 1344761938);
			}
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Error: MD5 mismatch. Maybe the extension file was downloaded and saved as a text file by the ' . 'browser and thereby corrupted!? (Always select "All" filetype when saving extensions)', 1344761991);
		}
		return $output;
	}

}


?>