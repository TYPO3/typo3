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
 * class.tx_language_llxmlparser.php
 *
 * Parser for XML locallang file
 *
 * @author Dominique Feyer <dfeyer@reelpeek.net>
 */
 
/**
 * Parser for XML locallang file
 *
 * @package TYPO3
 * @subpackage core
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
class tx_language_LlxmlParser extends tx_language_XmlParserAbstract {

	/**
	 * Associative array of "filename => parsed data" pairs.
	 *
	 * @var array
	 */
	protected $parsedTargetFiles;

	/**
	 * Returns parsed representation of XML file.
	 * 
	 * @param  string $sourcePath	Source file path
	 * @param  string $languageKey	Language key
	 * @param  string $charset		Charest
	 * @return array
	 */
	public function getParsedData($sourcePath, $languageKey, $charset) {
		$this->sourcePath = $sourcePath;
		$this->languageKey = $languageKey;
		$this->charset = $charset;
		
		// Parse source
		$parsedSource = $this->parseXmlFile();

		// Parse target
		$localizedTargetPath = t3lib_div::getFileAbsFileName(t3lib_div::llXmlAutoFileName($this->sourcePath, $this->languageKey));
		$targetPath = ($this->languageKey != 'default' && @is_file($localizedTargetPath)) ? $localizedTargetPath : $this->sourcePath;

		try {
			$parsedTarget = $this->getParsedTargetData($targetPath);
		} catch (tx_language_InvalidXMLFileException $e) {
			$parsedTarget = $this->getParsedTargetData($this->sourcePath);
		}

		return t3lib_div::array_merge_recursive_overrule($parsedSource, $parsedTarget);
	}

	/**
	 * Returns array representation of XLIFF data, starting from a root node.
	 * 
	 * @param  SimpleXMLElement $root	XML root element
	 * @param  string $element			Target or Source
	 * @return array
	 */
	protected function _doParsingFromRoot(SimpleXMLElement $root, $element) {
		$parsedData = array();
		$bodyOfFileTag = $root->data->languageKey;

		foreach ($bodyOfFileTag->children() as $translationElement) {
			if ($translationElement->getName() === 'label') {
					// If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)

				$parsedData[(string)$translationElement['index']][0] = array(
					$element => (string)$translationElement,
				);
			}
		}

		return $parsedData;
	}
	
	/**
	 * Returns array representation of XLIFF data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed XLIFF
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @todo: Support "approved" attribute
	 */
	protected function doParsingFromRoot(SimpleXMLElement $root) {
		return $this->_doParsingFromRoot($root, 'source');
	}

	/**
	 * Returns array representation of XLIFF data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed XLIFF
	 * @author Karol Gusak <firstname@lastname.eu>
	 * @todo: Support "approved" attribute
	 */
	protected function doParsingTargetFromRoot(SimpleXMLElement $root) {
		return $this->_doParsingFromRoot($root, 'target');
	}

	/**
	 * Returns parsed representation of XML file.
	 *
	 * Parses XML if it wasn't done before. Caches parsed data.
	 *
	 * @param  string $path An absolute path to XML file
	 * @return array 		Parsed XML file
	 */
	public function getParsedTargetData($path) {
		if (!isset($this->parsedTargetFiles[$path])) {
			$this->parsedTargetFiles[$path] = $this->parseXmlTargetFile($path);
		}
		return $this->parsedTargetFiles[$path];
	}

	/**
	 * Reads and parses XML file and returns internal representation of data.
	 * 
	 * @throws tx_language_InvalidXMLFileException
	 * @param  string $targetPath	Path of the target file
	 * @return array
	 */
	protected function parseXmlTargetFile($targetPath) {
		$rootXmlNode = FALSE;
		
		if (file_exists($targetPath)) {
			$rootXmlNode = simplexml_load_file($targetPath, 'SimpleXmlElement', \LIBXML_NOWARNING);
		}

		if (!isset($rootXmlNode) || $rootXmlNode === FALSE) {
			throw new tx_language_InvalidXMLFileException('The path provided does not point to existing and accessible well-formed XML file (' . $targetPath . ').', 1278155987);
		}

		return $this->doParsingTargetFromRoot($rootXmlNode);
	}
}

?>
