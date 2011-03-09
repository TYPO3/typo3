<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Extbase Team
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Utilities to process FlexForms
 *
 * @package Extbase
 * @subpackage Service
 */
class Tx_Extbase_Service_FlexFormService implements t3lib_Singleton {

	/**
	 * Parses the FlexForm content and converts it to an array
	 * The resulting array will be multi-dimensional, as a value "bla.blubb"
	 * results in two levels, and a value "bla.blubb.bla" results in three levels.
	 *
	 * Note: multi-language FlexForms are not supported yet
	 *
	 * @param string $flexFormContent FlexForm xml string
	 * @param string $languagePointer language pointer used in the FlexForm
	 * @param string $valuePointer value pointer used in the FlexForm
	 * @return array the processed array
	 */
	public function convertFlexFormContentToArray($flexFormContent, $languagePointer = 'lDEF', $valuePointer = 'vDEF') {
		$settings = array();

		$flexFormArray = t3lib_div::xml2array($flexFormContent);
		$flexFormArray = (isset($flexFormArray['data']) ? $flexFormArray['data'] : array());
		foreach(array_values($flexFormArray) as $languages) {
			if (!is_array($languages[$languagePointer])) {
				continue;
			}

			foreach($languages[$languagePointer] as $valueKey => $valueDefinition) {
				if (strpos($valueKey, '.') === false) {
					$settings[$valueKey] = $this->walkFlexFormNode($valueDefinition, $valuePointer);
				} else {
					$valueKeyParts = explode('.', $valueKey);
					$currentNode =& $settings;

					foreach ($valueKeyParts as $valueKeyPart) {
						$currentNode =& $currentNode[$valueKeyPart];
					}

					if (is_array($valueDefinition)) {
						if (array_key_exists($valuePointer, $valueDefinition)) {
							$currentNode = $valueDefinition[$valuePointer];
						} else {
							$currentNode = $this->walkFlexFormNode($valueDefinition, $valuePointer);
						}
					} else {
						$currentNode = $valueDefinition;
					}
				}
			}
		}
		return $settings;
	}

	/**
	 * Parses a flexform node recursively and takes care of sections etc
	 *
	 * @param array $nodeArray The flexform node to parse
	 * @param string $valuePointer The valuePointer to use for value retrieval
	 * @return array
	 */
	public function walkFlexFormNode($nodeArray, $valuePointer = 'vDEF') {
		if (is_array($nodeArray)) {
			$return = array();

			foreach ($nodeArray as $nodeKey => $nodeValue) {
				if ($nodeKey === $valuePointer) {
					return $nodeValue;
				}

				if (in_array($nodeKey, array('el', '_arrayContainer'))) {
					return $this->walkFlexFormNode($nodeValue, $valuePointer);
				}

				if (substr($nodeKey, 0, 1) === '_') {
					continue;
				}

				if (strpos($nodeKey, '.')) {
					$nodeKeyParts = explode('.', $nodeKey);
					$currentNode =& $return;

					for ($i = 0; $i < (count($nodeKeyParts) - 1); $i++) {
						$currentNode =& $currentNode[$nodeKeyParts[$i]];
					}

					$newNode = array(next($nodeKeyParts) => $nodeValue);
					$currentNode = $this->walkFlexFormNode($newNode, $valuePointer);
				} else if (is_array($nodeValue)) {
					if (array_key_exists($valuePointer, $nodeValue)) {
						$return[$nodeKey] = $nodeValue[$valuePointer];
					} else {
						$return[$nodeKey] = $this->walkFlexFormNode($nodeValue, $valuePointer);
					}
				} else {
					$return[$nodeKey] = $nodeValue;
				}
			}
			return $return;
		}

		return $nodeArray;
	}

}
?>