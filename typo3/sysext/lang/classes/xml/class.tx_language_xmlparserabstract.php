<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Dominique Feyer (dfeyer@reelpeek.net)
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
 * class.tx_language_xmlparserabstract.php
 *
 * Abstract class for XML based parser
 *
 * @author Dominique Feyer <dfeyer@reelpeek.net>
 */

/**
 * Abstract class for XML based parser
 *
 * @package TYPO3
 * @subpackage core
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
abstract class tx_language_XmlParserAbstract implements tx_Language_ParserInterface {

	protected $sourcePath;
	protected $languageKey;
	protected $charset;
	protected $parsedData;

	/**
	 * Returns parsed representation of XML file.
	 * 
	 * @throws tx_language_FileNotFoundException
	 * @param  string $sourcePath	Source file path
	 * @param  string $languageKey	Language key
	 * @param  string $charset		File charset
	 * @return array
	 */
	public function getParsedData($sourcePath, $languageKey, $charset) {
		$this->sourcePath = $sourcePath;
		$this->languageKey = $languageKey;
		$this->charset = $charset;

		if (($this->languageKey != 'default' && $this->languageKey != 'en')) {
			$this->sourcePath = t3lib_div::getFileAbsFileName(
				t3lib_div::llXmlAutoFileName($this->sourcePath, $this->languageKey)
			);

			if (!@is_file($this->sourcePath)) {
				throw new tx_language_FileNotFoundException(
					'Localization file does not exist',
					1306332397
				);
			}
		}
		
		return $this->parseXmlFile();
	}

	/**
	 * Load the current XML file before processing
	 * 
	 * @throws tx_language_InvalidXMLFileException
	 * @return array An array representing parsed XML file (structure depends on concrete parser)
	 */
	protected function parseXmlFile() {
		$rootXmlNode = simplexml_load_file($this->sourcePath, 'SimpleXmlElement', \LIBXML_NOWARNING);

		if (!isset($rootXmlNode) || $rootXmlNode === FALSE) {
			throw new tx_language_InvalidXMLFileException(
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