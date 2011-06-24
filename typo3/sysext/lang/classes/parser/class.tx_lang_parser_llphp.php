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
 * @author Dominique Feyer <dfeyer@reelpeek.net>
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class tx_lang_parser_Llphp implements tx_lang_parser {

	/** @var string */
	protected $cacheFileName;

	/** @var t3lib_cs */
	protected $csConvObj;

	/** @var string */
	protected $hashSource;

	/** @var string */
	protected $sourceCharset;

	/** @var string */
	protected $targetCharset;

	/**
	 * Initializes the parser.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->createCsConvObject();
	}

	/**
	 * Returns parsed representation of PHP locallang file.
	 *
	 * @throws RuntimeException
	 * @param string $sourcePath Source file path
	 * @param string $languageKey Language key
	 * @param string $charset Charset
	 * @return array
	 */
	public function getParsedData($sourcePath, $languageKey, $charset = '') {
		$this->validateParameters($sourcePath, $languageKey);
		$this->setCharsets($languageKey, $charset);
		$this->generateCacheFileName($sourcePath, $languageKey);

		if (!file_exists($this->cacheFileName)) {
			$LOCAL_LANG = $this->generateCacheFile($sourcePath, $languageKey);
		} else {
			$LOCAL_LANG = $this->getContentFromCacheFile();
		}

		$xliff = $this->convertToXLIFF($LOCAL_LANG);

		return $xliff;
	}

	/**
	 * Converts the LOCAL_LANG array to XLIFF structure.
	 *
	 * @param array $LOCAL_LANG
	 * @return array
	 */
	protected function convertToXLIFF(array $LOCAL_LANG) {
		foreach ($LOCAL_LANG as &$keysLabels) {
			foreach ($keysLabels as &$label) {
				$label = array(0 => array(
					'target' => $label,
				));
			}
			unset($label);
		}
		return $LOCAL_LANG;
	}

	/**
	 * Creates a character conversion object.
	 *
	 * @return void
	 */
	protected function createCsConvObject() {
		if (is_object($GLOBALS['LANG'])) {
			$this->csConvObj = $GLOBALS['LANG']->csConvObj;
		} elseif (is_object($GLOBALS['TSFE'])) {
			$this->csConvObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			$this->csConvObj = t3lib_div::makeInstance('t3lib_cs');
		}
	}

	/**
	 * Generates the cache file.
	 *
	 * @throws RuntimeException
	 * @param string $sourcePath
	 * @param string $languageKey
	 * @return array
	 */
	protected function generateCacheFile($sourcePath, $languageKey) {
		$LOCAL_LANG = array();

		// Get PHP data
		include($sourcePath);
		if (!is_array($LOCAL_LANG)) {
			$fileName = substr($sourcePath, strlen(PATH_site));
			throw new RuntimeException(
				'TYPO3 Fatal Error: "' . $fileName . '" is no TYPO3 language file!',
				1308898491
			);
		}

		// Converting the default language (English)
		// This needs to be done for a few accented loan words and extension names
		if (is_array($LOCAL_LANG['default']) && $this->targetCharset !== 'iso-8859-1') {
			foreach ($LOCAL_LANG['default'] as &$labelValue) {
				$labelValue = $this->csConvObj->conv($labelValue, 'iso-8859-1', $this->targetCharset);
			}
			unset($labelValue);
		}

		if ($languageKey !== 'default' && is_array($LOCAL_LANG[$languageKey]) && $this->sourceCharset != $this->targetCharset) {
			foreach ($LOCAL_LANG[$languageKey] as &$labelValue) {
				$labelValue = $this->csConvObj->conv($labelValue, $this->sourceCharset, $this->targetCharset);
			}
			unset($labelValue);
		}

		// Cache the content now:
		$serContent = array('origFile' => $this->hashSource, 'LOCAL_LANG' => array('default' => $LOCAL_LANG['default'], $languageKey => $LOCAL_LANG[$languageKey]));
		$res = t3lib_div::writeFileToTypo3tempDir($this->cacheFileName, serialize($serContent));
		if ($res) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: "' . $res,
				1308898501
			);
		}
		return $LOCAL_LANG;
	}

	/**
	 * Generates the name of the cached file.
	 *
	 * @param string $sourcePath
	 * @param string $languageKey
	 * @return void
	 */
	protected function generateCacheFileName($sourcePath, $languageKey) {
		$this->hashSource = substr($sourcePath, strlen(PATH_site)) . '|' . date('d-m-Y H:i:s', filemtime($sourcePath)) . '|version=2.3';
		$this->cacheFileName = PATH_site . 'typo3temp/llxml/' .
			substr(basename($sourcePath), 10, 15) .
			'_' . t3lib_div::shortMD5($this->hashSource) . '.' . $languageKey .
			'.' . $this->targetCharset . '.cache';
	}

	/**
	 * Obtains the content from the cache file.
	 *
	 * @return array
	 */
	protected function getContentFromCacheFile() {
		$serContent = (array)unserialize(file_get_contents($this->cacheFileName));
		$LOCAL_LANG = $serContent['LOCAL_LANG'];
		return (array)$LOCAL_LANG;
	}

	/**
	 * Checks if the file is within the web root.
	 *
	 * @param string $fileName
	 * @return bool
	 */
	protected function isWithinWebRoot($fileName) {
		return (bool)t3lib_div::getFileAbsFileName($fileName);
	}

	/**
	 * Sets character sets for the language key.
	 *
	 * @param string $languageKey
	 * @param string $charset
	 * @return void
	 */
	protected function setCharsets($languageKey, $charset) {
		$this->sourceCharset = $this->csConvObj->parse_charset($this->csConvObj->charSetArray[$languageKey]
			? $this->csConvObj->charSetArray[$languageKey] : 'iso-8859-1');
		if ($charset) {
			$this->targetCharset = $this->csConvObj->parse_charset($charset);
		} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) {
				// When forceCharset is set, we store ALL labels in this charset!!!
			$this->targetCharset = $this->csConvObj->parse_charset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
		} else {
			$this->targetCharset = $this->csConvObj->parse_charset($this->csConvObj->charSetArray[$languageKey]
				? $this->csConvObj->charSetArray[$languageKey] : 'iso-8859-1');
		}
	}

	/**
	 * Validates parameters for the function.
	 *
	 * @throws RuntimeException
	 * @param string $sourcePath
	 * @param string $languageKey
	 * @return void
	 */
	protected function validateParameters($sourcePath, $languageKey) {
		if (!$this->isWithinWebRoot($sourcePath) || !@is_file($sourcePath) || !$languageKey) {
			throw new RuntimeException(sprintf('Invalid source path (%s) or languageKey (%s)', $sourcePath, $languageKey), 1309245002);
		}
	}
}

?>