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
 * Parser for XML locallang file.
 *
 * @package	TYPO3
 * @subpackage	t3lib
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
class t3lib_l10n_parser_Llxml extends t3lib_l10n_parser_AbstractXml {

	/**
	 * Associative array of "filename => parsed data" pairs.
	 *
	 * @var array
	 */
	protected $parsedTargetFiles;

	/**
	 * Returns parsed representation of XML file.
	 *
	 * @param string $sourcePath Source file path
	 * @param string $languageKey Language key
	 * @param string $charset Charset
	 * @return array
	 */
	public function getParsedData($sourcePath, $languageKey, $charset = '') {
		$this->sourcePath = $sourcePath;
		$this->languageKey = $languageKey;
		$this->charset = $this->getCharset($languageKey, $charset);

			// Parse source
		$parsedSource = $this->parseXmlFile();

			// Parse target
		$localizedTargetPath = t3lib_div::getFileAbsFileName(t3lib_div::llXmlAutoFileName($this->sourcePath, $this->languageKey));
		$targetPath = ($this->languageKey !== 'default' && @is_file($localizedTargetPath)) ? $localizedTargetPath : $this->sourcePath;

		try {
			$parsedTarget = $this->getParsedTargetData($targetPath);
		} catch (t3lib_l10n_exception_InvalidXmlFile $e) {
			$parsedTarget = $this->getParsedTargetData($this->sourcePath);
		}

		$LOCAL_LANG = array();
		$LOCAL_LANG[$languageKey] = t3lib_div::array_merge_recursive_overrule($parsedSource, $parsedTarget);

		return $LOCAL_LANG;
	}

	/**
	 * Returns array representation of XLIFF data, starting from a root node.
	 *
	 * @param SimpleXMLElement $root XML root element
	 * @param string $element Target or Source
	 * @return array
	 */
	protected function doParsingFromRootForElement(SimpleXMLElement $root, $element) {
		$bodyOfFileTag = $root->data->languageKey;

			// Check if the source llxml file contains localized records
		$localizedBodyOfFileTag = $root->data->xpath("languageKey[@index='" . $this->languageKey . "']");

		if ($element === 'source' || $this->languageKey === 'default') {
			$parsedData = $this->getParsedDataForElement($bodyOfFileTag, $element);
		} else {
			$parsedData = array();
		}
		if ($element === 'target' && isset($localizedBodyOfFileTag[0]) && $localizedBodyOfFileTag[0] instanceof SimpleXMLElement) {
			$parsedDataTarget = $this->getParsedDataForElement($localizedBodyOfFileTag[0], $element);
			$mergedData = array_merge($parsedData, $parsedDataTarget);

			if ($this->languageKey === 'default') {
				$parsedData = array_intersect_key($mergedData, $parsedData, $parsedDataTarget);
			} else {
				$parsedData = array_intersect_key($mergedData, $parsedDataTarget);
			}
		}

		return $parsedData;
	}

	/**
	 * Parse the given language key tag
	 *
	 * @param SimpleXMLElement $bodyOfFileTag
	 * @param string $element
	 * @return array
	 */
	protected function getParsedDataForElement(SimpleXMLElement $bodyOfFileTag, $element) {
		$parsedData = array();

		if (count($bodyOfFileTag->children()) == 0) {
				// Check for externally-referenced resource:
				// <languageKey index="fr">EXT:yourext/path/to/localized/locallang.xml</languageKey>
			$reference = sprintf('%s', $bodyOfFileTag);
			if (substr($reference, -4) === '.xml') {
				return $this->getParsedTargetData(t3lib_div::getFileAbsFileName($reference));
			}
		}
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
	 */
	protected function doParsingFromRoot(SimpleXMLElement $root) {
		return $this->doParsingFromRootForElement($root, 'source');
	}

	/**
	 * Returns array representation of XLIFF data, starting from a root node.
	 *
	 * @param \SimpleXMLElement $root A root node
	 * @return array An array representing parsed XLIFF
	 */
	protected function doParsingTargetFromRoot(SimpleXMLElement $root) {
		return $this->doParsingFromRootForElement($root, 'target');
	}

	/**
	 * Returns parsed representation of XML file.
	 *
	 * Parses XML if it wasn't done before. Caches parsed data.
	 *
	 * @param string $path An absolute path to XML file
	 * @return array Parsed XML file
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
	 * @throws t3lib_l10n_exception_InvalidXmlFile
	 * @param string $targetPath Path of the target file
	 * @return array
	 */
	protected function parseXmlTargetFile($targetPath) {
		$rootXmlNode = FALSE;

		if (file_exists($targetPath)) {
			$rootXmlNode = simplexml_load_file($targetPath, 'SimpleXmlElement', \LIBXML_NOWARNING);
		}

		if (!isset($rootXmlNode) || $rootXmlNode === FALSE) {
			throw new t3lib_l10n_exception_InvalidXmlFile('The path provided does not point to existing and accessible well-formed XML file (' . $targetPath . ').', 1278155987);
		}

		return $this->doParsingTargetFromRoot($rootXmlNode);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/l10n/parser/class.t3lib_l10n_parser_llxml.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/l10n/parser/class.t3lib_l10n_parser_llxml.php']);
}

?>