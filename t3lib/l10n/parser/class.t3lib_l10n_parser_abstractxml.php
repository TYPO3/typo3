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
 * Abstract class for XML based parser.
 *
 * @package	TYPO3
 * @subpackage	t3lib
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
abstract class t3lib_l10n_parser_AbstractXml implements t3lib_l10n_parser {

	/**
	 * @var string
	 */
	protected $sourcePath;

	/**
	 * @var string
	 */
	protected $languageKey;

	/**
	 * @var string
	 */
	protected $charset;

	/**
	 * Returns parsed representation of XML file.
	 *
	 * @throws t3lib_l10n_exception_FileNotFound
	 * @param string $sourcePath Source file path
	 * @param string $languageKey Language key
	 * @param string $charset File charset
	 * @return array
	 */
	public function getParsedData($sourcePath, $languageKey, $charset = '') {
		$this->sourcePath = $sourcePath;
		$this->languageKey = $languageKey;
		$this->charset = $this->getCharset($languageKey, $charset);

		if (($this->languageKey !== 'default' && $this->languageKey !== 'en')) {
			$this->sourcePath = t3lib_div::getFileAbsFileName(
				t3lib_div::llXmlAutoFileName($this->sourcePath, $this->languageKey)
			);
			if (!@is_file($this->sourcePath)) {
						// Global localization is not available, try split localization file
					$this->sourcePath = t3lib_div::getFileAbsFileName(
					t3lib_div::llXmlAutoFileName($sourcePath, $languageKey, TRUE)
				);
			}

			if (!@is_file($this->sourcePath)) {
				throw new t3lib_l10n_exception_FileNotFound(
					'Localization file does not exist',
					1306332397
				);
			}
		}

		$LOCAL_LANG = array();
		$LOCAL_LANG[$languageKey] = $this->parseXmlFile();

		return $LOCAL_LANG;
	}

	/**
	 * Gets the character set to use.
	 *
	 * @param string $languageKey
	 * @param string $charset
	 * @return string
	 */
	protected function getCharset($languageKey, $charset = '') {
		/** @var $csConvObj t3lib_cs */
		if (is_object($GLOBALS['LANG'])) {
			$csConvObj = $GLOBALS['LANG']->csConvObj;
		} elseif (is_object($GLOBALS['TSFE'])) {
			$csConvObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			$csConvObj = t3lib_div::makeInstance('t3lib_cs');
		}

		if ($charset !== '') {
			$targetCharset = $csConvObj->parse_charset($charset);
		} else {
			$targetCharset = 'utf-8';
		}

		return $targetCharset;
	}

	/**
	 * Loads the current XML file before processing.
	 *
	 * @throws t3lib_l10n_exception_InvalidXmlFile
	 * @return array An array representing parsed XML file (structure depends on concrete parser)
	 */
	protected function parseXmlFile() {
		$rootXmlNode = simplexml_load_file($this->sourcePath, 'SimpleXmlElement', \LIBXML_NOWARNING);

		if (!isset($rootXmlNode) || $rootXmlNode === FALSE) {
			throw new t3lib_l10n_exception_InvalidXmlFile(
				'The path provided does not point to existing and accessible well-formed XML file.',
				1278155987
			);
		}

		return $this->doParsingFromRoot($rootXmlNode);
	}

	/**
	 * Returns array representation of XML data, starting from a root node.
	 *
	 * @abstract
	 * @param SimpleXMLElement $root A root node
	 * @return array An array representing the parsed XML file
	 */
	abstract protected function doParsingFromRoot(SimpleXMLElement $root);
}

?>