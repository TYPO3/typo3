<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Dominique Feyer <dfeyer@reelpeek.net>
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
 * Parser for PHP locallang array.
 *
 * @package	TYPO3
 * @subpackage	tx_lang
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
class tx_lang_parser_Llphp implements tx_lang_parser {

	/**
	 * Returns parsed representation of PHP locallang file.
	 *
	 * @throws RuntimeException
	 * @param string $sourcePath Source file path
	 * @param string $languageKey Language key
	 * @param string $charset Charset
	 * @return void
	 */
	public function getParsedData($sourcePath, $languageKey, $charset = '') {
		/** @var $csConvObj t3lib_cs */
		if (is_object($GLOBALS['LANG'])) {
			$csConvObj = $GLOBALS['LANG']->csConvObj;
		} elseif (is_object($GLOBALS['TSFE'])) {
			$csConvObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			$csConvObj = t3lib_div::makeInstance('t3lib_cs');
		}

		if (@is_file($sourcePath) && $languageKey) {

				// Set charsets:
			$sourceCharset = $csConvObj->parse_charset($csConvObj->charSetArray[$languageKey] ? $csConvObj->charSetArray[$languageKey] : 'iso-8859-1');
			if ($charset) {
				$targetCharset = $csConvObj->parse_charset($charset);
			} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) {
					// When forceCharset is set, we store ALL labels in this charset!!!
				$targetCharset = $csConvObj->parse_charset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
			} else {
				$targetCharset = $csConvObj->parse_charset($csConvObj->charSetArray[$languageKey] ? $csConvObj->charSetArray[$languageKey] : 'iso-8859-1');
			}

				// Cache file name:
			$hashSource = substr($sourcePath, strlen(PATH_site)) . '|' . date('d-m-Y H:i:s', filemtime($sourcePath)) . '|version=2.3';
			$cacheFileName = PATH_site . 'typo3temp/llxml/' .
					substr(basename($sourcePath), 10, 15) .
					'_' . t3lib_div::shortMD5($hashSource) . '.' . $languageKey . '.' . $targetCharset . '.cache';
				// Check if cache file exists...
			if (!@is_file($cacheFileName)) {
				$LOCAL_LANG = NULL;
					// Get PHP data
				include($sourcePath);
				if (!is_array($LOCAL_LANG)) {
					$fileName = substr($fileRef, strlen(PATH_site));
					throw new RuntimeException(
						'TYPO3 Fatal Error: "' . $fileName . '" is no TYPO3 language file!',
						1308898491
					);
				}

					// Converting the default language (English)
					// This needs to be done for a few accented loan words and extension names
				if (is_array($LOCAL_LANG['default']) && $targetCharset !== 'iso-8859-1') {
					foreach ($LOCAL_LANG['default'] as &$labelValue) {
						$labelValue = $csConvObj->conv($labelValue, 'iso-8859-1', $targetCharset);
					}
					unset($labelValue);
				}

				if ($langKey !== 'default' && is_array($LOCAL_LANG[$langKey]) && $sourceCharset != $targetCharset) {
					foreach ($LOCAL_LANG[$langKey] as &$labelValue) {
						$labelValue = $csConvObj->conv($labelValue, $sourceCharset, $targetCharset);
					}
					unset($labelValue);
				}

					// Cache the content now:
				$serContent = array('origFile' => $hashSource, 'LOCAL_LANG' => array('default' => $LOCAL_LANG['default'], $langKey => $LOCAL_LANG[$langKey]));
				$res = t3lib_div::writeFileToTypo3tempDir($cacheFileName, serialize($serContent));
				if ($res) {
					throw new RuntimeException(
						'TYPO3 Fatal Error: "' . $res,
						1308898501
					);
				}
			} else {
					// Get content from cache:
				$serContent = unserialize(t3lib_div::getUrl($cacheFileName));
				$LOCAL_LANG = $serContent['LOCAL_LANG'];
			}

			return $LOCAL_LANG;
		}
	}

}

?>