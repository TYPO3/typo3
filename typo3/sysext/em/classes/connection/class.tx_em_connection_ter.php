<?php
/* **************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage EM
 */
class tx_em_Connection_Ter {
	var $wsdlURL;

	/**
	 * Extension manager module
	 *
	 * @var SC_mod_tools_em_index
	 */
	var $emObj;

	public function __construct($emObj) {
		$this->emObj = $emObj;
	}

	/**
	 * Fetches an extension from the given mirror
	 *
	 * @param	string		$extKey	Extension Key
	 * @param	string		$version	Version to install
	 * @param	string		$expectedMD5	Expected MD5 hash of extension file
	 * @param	string		$mirrorURL	URL of mirror to use
	 * @return	mixed		T3X data (array) or error message (string)
	 */
	function fetchExtension($extKey, $version, $expectedMD5, $mirrorURL) {
		$extPath = t3lib_div::strtolower($extKey);
		$mirrorURL .= $extPath{0} . '/' . $extPath{1} . '/' . $extPath . '_' . $version . '.t3x';
		$t3x = t3lib_div::getURL($mirrorURL, 0, array(TYPO3_user_agent));
		$MD5 = md5($t3x);

		if ($t3x === FALSE) {
			return 'The T3X file could not be fetched. Possible reasons: network problems, allow_url_fopen is off, curl is not enabled in Install tool.';
		}

		if ($MD5 == $expectedMD5) {
			// Fetch and return:
			return $this->decodeExchangeData($t3x);
		} else {
			return 'Error: MD5 hash of downloaded file not as expected:<br />' . $MD5 . ' != ' . $expectedMD5;
		}
	}

	/**
	 * Fetches an extensions l10n file from the given mirror
	 *
	 * @param string $extKey	Extension Key
	 * @param string $lang	The language code of the translation to fetch
	 * @param string $mirrorURL	URL of mirror to use
	 * @return mixed	Array containing l10n data or error message (string)
	 */
	function fetchTranslation($extKey, $lang, $mirrorURL) {
		$extPath = t3lib_div::strtolower($extKey);
		$mirrorURL .= $extPath{0} . '/' . $extPath{1} . '/' . $extPath . '-l10n/' . $extPath . '-l10n-' . $lang . '.zip';
		$l10n = t3lib_div::getURL($mirrorURL, 0, array(TYPO3_user_agent));

		if ($l10n !== false) {
			return array($l10n);
		} else {
			return 'Error: Translation could not be fetched.';
		}
	}

	/**
	 * Install translations for all selected languages for an extension
	 *
	 * @param string $extKey		The extension key to install the translations for
	 * @param string $lang		Language code of translation to fetch
	 * @param string $mirrorURL		Mirror URL to fetch data from
	 * @return mixed	true on success, error string on fauilure
	 */
	function updateTranslation($extKey, $lang, $mirrorURL) {
		$l10n = $this->fetchTranslation($extKey, $lang, $mirrorURL);
		if (is_array($l10n)) {
			$file = PATH_site . 'typo3temp/' . $extKey . '-l10n-' . $lang . '.zip';
			$path = 'l10n/' . $lang . '/';
			if (!is_dir(PATH_typo3conf . $path)) {
				t3lib_div::mkdir_deep(PATH_typo3conf, $path);
			}
			t3lib_div::writeFile($file, $l10n[0]);
			if (tx_em_Tools::unzip($file, PATH_typo3conf . $path)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Fetches extension l10n status from the given mirror
	 *
	 * @param string	 $extKey	Extension Key
	 * @param string	 $mirrorURL	URL of mirror to use
	 * @return mixed	Array containing l10n status data or FALSE if no status could be fetched
	 */
	function fetchTranslationStatus($extKey, $mirrorURL) {
		$extPath = t3lib_div::strtolower($extKey);
		$mirrorURL .= $extPath{0} . '/' . $extPath{1} . '/' . $extPath . '-l10n/' . $extPath . '-l10n.xml';
		$remote = t3lib_div::getURL($mirrorURL, 0, array(TYPO3_user_agent));

		if ($remote !== false) {
			$parsed = $this->emObj->xmlhandler->parseL10nXML($remote);
			return $parsed['languagePackIndex'];
		}

		return FALSE;
	}

	/**
	 * Decode server data
	 * This is information like the extension list, extension information etc., return data after uploads (new em_conf)
	 *
	 * @param	string		Data stream from remove server
	 * @return	mixed		On success, returns an array with data array and stats array as key 0 and 1. Otherwise returns error string
	 * @see fetchServerData(), processRepositoryReturnData()
	 */
	function decodeServerData($externalData) {
		$parts = explode(':', $externalData, 4);
		$dat = base64_decode($parts[2]);
		// compare hashes ignoring any leading whitespace. See bug #0000365.
		if (ltrim($parts[0]) == md5($dat)) {
			if ($parts[1] == 'gzcompress') {
				if (function_exists('gzuncompress')) {
					$dat = gzuncompress($dat);
				} else {
					return 'Decoding Error: No decompressor available for compressed content. gzuncompress() function is not available!';
				}
			}
			$listArr = unserialize($dat);

			if (is_array($listArr)) {
				return $listArr;
			} else {
				return 'Error: Unserialized information was not an array - strange!';
			}
		} else {
			return 'Error: MD5 hashes in T3X data did not match!';
		}
	}

	/**
	 * Decodes extension upload array.
	 * This kind of data is when an extension is uploaded to TER
	 *
	 * @param	string		Data stream
	 * @return	mixed		Array with result on success, otherwise an error string.
	 */
	function decodeExchangeData($str) {
		$parts = explode(':', $str, 3);
		if ($parts[1] == 'gzcompress') {
			if (function_exists('gzuncompress')) {
				$parts[2] = gzuncompress($parts[2]);
			} else {
				return 'Decoding Error: No decompressor available for compressed content. gzcompress()/gzuncompress() functions are not available!';
			}
		}
		if (md5($parts[2]) == $parts[0]) {
			$output = unserialize($parts[2]);
			if (is_array($output)) {
				return array($output, '');
			} else {
				return 'Error: Content could not be unserialized to an array. Strange (since MD5 hashes match!)';
			}
		} else {
			return 'Error: MD5 mismatch. Maybe the extension file was downloaded and saved as a text file by the browser and thereby corrupted!? (Always select "All" filetype when saving extensions)';
		}
	}


	/**
	 * Encodes extension upload array
	 *
	 * @param	array		Array containing extension
	 * @return	string		Content stream
	 */
	function makeUploadDataFromarray($uploadArray) {
		if (is_array($uploadArray)) {
			$serialized = serialize($uploadArray);
			$md5 = md5($serialized);

			$content = $md5 . ':';
			$content .= 'gzcompress:';
			$content .= gzcompress($serialized);
		}
		return $content;
	}

	/**
	 * Upload extension to ter
	 * @param  $em
	 * @return
	 */
	function uploadToTER($em) {
		$uArr = $this->emObj->extensionDetails->makeUploadarray($em['extKey'], $em['extInfo']);
		if (!is_array($uArr)) {
			return $uArr;
		}

		// Render new version number:
		$newVersionBase = $em['extInfo']['EM_CONF']['version'];
		switch ((string) $em['upload']['mode']) {
			case 'new_dev':
				$cmd = 'dev';
				break;
			case 'new_sub':
				$cmd = 'sub';
				break;
			case 'new_main':
				$cmd = 'main';
				break;
			case 'custom':
				$newVersionBase = $em['upload']['version'];
			case 'latest':
			default:
				$cmd = '';
				break;
		}
		$versionArr = tx_em_Tools::renderVersion($newVersionBase, $cmd);
		$em['version'] = $versionArr['version'];

		// Create dependency / conflict information:
		$dependenciesArr = array();
		$extKeysArr = $uArr['EM_CONF']['constraints']['depends'];

		if (is_array($extKeysArr)) {
			foreach ($extKeysArr as $extKey => $version) {
				if (strlen($extKey)) {
					$dependenciesArr[] = array(
						'kind' => 'depends',
						'extensionKey' => utf8_encode($extKey),
						'versionRange' => utf8_encode($version),
					);
				}
			}
		}

		$extKeysArr = $uArr['EM_CONF']['constraints']['conflicts'];
		if (is_array($extKeysArr)) {
			foreach ($extKeysArr as $extKey => $version) {
				if (strlen($extKey)) {
					$dependenciesArr[] = array(
						'kind' => 'conflicts',
						'extensionKey' => utf8_encode($extKey),
						'versionRange' => utf8_encode($version),
					);
				}
			}
		}
		// FIXME: This part must be removed, when the problem is solved on the TER-Server #5919
		if (count($dependenciesArr) == 1) {
			$dependenciesArr[] = array(
				'kind' => 'depends',
				'extensionKey' => '',
				'versionRange' => '',
			);
		}
		// END for Bug #5919

		// Compile data for SOAP call:
		$accountData = array(
			'username' => $em['user']['fe_u'],
			'password' => $em['user']['fe_p']
		);
		$extensionData = array(
			'extensionKey' => utf8_encode($em['extKey']),
			'version' => utf8_encode($em['version']),
			'metaData' => array(
				'title' => utf8_encode($uArr['EM_CONF']['title']),
				'description' => utf8_encode($uArr['EM_CONF']['description']),
				'category' => utf8_encode($uArr['EM_CONF']['category']),
				'state' => utf8_encode($uArr['EM_CONF']['state']),
				'authorName' => utf8_encode($uArr['EM_CONF']['author']),
				'authorEmail' => utf8_encode($uArr['EM_CONF']['author_email']),
				'authorCompany' => utf8_encode($uArr['EM_CONF']['author_company']),
			),
			'technicalData' => array(
				'dependencies' => $dependenciesArr,
				'loadOrder' => utf8_encode($uArr['EM_CONF']['loadOrder']),
				'uploadFolder' => (boolean) intval($uArr['EM_CONF']['uploadfolder']),
				'createDirs' => utf8_encode($uArr['EM_CONF']['createDirs']),
				'shy' => (boolean) intval($uArr['EM_CONF']['shy']),
				'modules' => utf8_encode($uArr['EM_CONF']['module']),
				'modifyTables' => utf8_encode($uArr['EM_CONF']['modify_tables']),
				'priority' => utf8_encode($uArr['EM_CONF']['priority']),
				'clearCacheOnLoad' => (boolean) intval($uArr['EM_CONF']['clearCacheOnLoad']),
				'lockType' => utf8_encode($uArr['EM_CONF']['lockType']),
			),
			'infoData' => array(
				'codeLines' => intval($uArr['misc']['codelines']),
				'codeBytes' => intval($uArr['misc']['codebytes']),
				'codingGuidelinesCompliance' => utf8_encode($uArr['EM_CONF']['CGLcompliance']),
				'codingGuidelinesComplianceNotes' => utf8_encode($uArr['EM_CONF']['CGLcompliance_note']),
				'uploadComment' => utf8_encode($em['upload']['comment']),
				'techInfo' => $uArr['techInfo'],
			),
		);

		$filesData = array();
		foreach ($uArr['FILES'] as $filename => $infoArr) {
			$filesData[] = array(
				'name' => utf8_encode($infoArr['name']),
				'size' => intval($infoArr['size']),
				'modificationTime' => intval($infoArr['mtime']),
				'isExecutable' => intval($infoArr['is_executable']),
				'content' => $infoArr['content'],
				'contentMD5' => $infoArr['content_md5'],
			);
		}

		$soap = t3lib_div::makeInstance('tx_em_Connection_Soap');
		$soap->init(array('wsdl' => $this->wsdlURL, 'soapoptions' => array('trace' => 1, 'exceptions' => 0)));
		$response = $soap->call(
			'uploadExtension',
			array(
				'accountData' => $accountData,
				'extensionData' => $extensionData,
				'filesData' => $filesData
			)
		);

		if ($response === FALSE) {
			switch (TRUE) {
				case is_string($soap->error):
					return $soap->error;
					break;
				default:
					return $soap->error->faultstring;
			}
		}

		return $response;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connection_ter.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connection_ter.php']);
}
?>