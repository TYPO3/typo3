<?php
namespace TYPO3\CMS\Core\Configuration\FlexForm;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains functions for manipulating flex form data
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Contains functions for manipulating flex form data
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FlexFormTools {

	// If set, the charset of data XML is converted to system charset.
	/**
	 * @todo Define visibility
	 */
	public $convertCharset = FALSE;

	// If set, section indexes are re-numbered before processing
	/**
	 * @todo Define visibility
	 */
	public $reNumberIndexesOfSectionData = FALSE;

	// Contains data structure when traversing flexform
	/**
	 * @todo Define visibility
	 */
	public $traverseFlexFormXMLData_DS = array();

	// Contains data array when traversing flexform
	/**
	 * @todo Define visibility
	 */
	public $traverseFlexFormXMLData_Data = array();

	// Options for array2xml() for flexform.
	// This will map the weird keys from the internal array to tags that could potentially be checked with a DTD/schema
	/**
	 * @todo Define visibility
	 */
	public $flexArray2Xml_options = array(
		'parentTagMap' => array(
			'data' => 'sheet',
			'sheet' => 'language',
			'language' => 'field',
			'el' => 'field',
			'field' => 'value',
			'field:el' => 'el',
			'el:_IS_NUM' => 'section',
			'section' => 'itemType'
		),
		'disableTypeAttrib' => 2
	);

	/**
	 * Reference to object called
	 *
	 * @todo Define visibility
	 */
	public $callBackObj = NULL;

	// Used for accumulation of clean XML
	/**
	 * @todo Define visibility
	 */
	public $cleanFlexFormXML = array();

	/**
	 * Handler for Flex Forms
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name of the flexform field to work on
	 * @param array $row The record data array
	 * @param object $callBackObj Object (passed by reference) in which the call back function is located
	 * @param string $callBackMethod_value Method name of call back function in object for values
	 * @return boolean If TRUE, error happened (error string returned)
	 * @todo Define visibility
	 */
	public function traverseFlexFormXMLData($table, $field, $row, $callBackObj, $callBackMethod_value) {
		if (!is_array($GLOBALS['TCA'][$table]) || !is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
			return 'TCA table/field was not defined.';
		}
		$this->callBackObj = $callBackObj;
		// Get Data Structure:
		$dataStructArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS($GLOBALS['TCA'][$table]['columns'][$field]['config'], $row, $table);
		// If data structure was ok, proceed:
		if (is_array($dataStructArray)) {
			// Get flexform XML data:
			$xmlData = $row[$field];
			// Convert charset:
			if ($this->convertCharset) {
				$xmlHeaderAttributes = \TYPO3\CMS\Core\Utility\GeneralUtility::xmlGetHeaderAttribs($xmlData);
				$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
				if ($storeInCharset) {
					$currentCharset = $GLOBALS['LANG']->charSet;
					$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData, $storeInCharset, $currentCharset, 1);
				}
			}
			$editData = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xmlData);
			if (!is_array($editData)) {
				return 'Parsing error: ' . $editData;
			}
			// Language settings:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;
			// Empty or invalid <meta>
			if (!is_array($editData['meta'])) {
				$editData['meta'] = array();
			}
			$editData['meta']['currentLangId'] = array();
			$languages = $this->getAvailableLanguages();
			foreach ($languages as $lInfo) {
				$editData['meta']['currentLangId'][] = $lInfo['ISOcode'];
			}
			if (!count($editData['meta']['currentLangId'])) {
				$editData['meta']['currentLangId'] = array('DEF');
			}
			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);
			if ($langChildren || $langDisabled) {
				$lKeys = array('DEF');
			} else {
				$lKeys = $editData['meta']['currentLangId'];
			}
			// Tabs sheets
			if (is_array($dataStructArray['sheets'])) {
				$sKeys = array_keys($dataStructArray['sheets']);
			} else {
				$sKeys = array('sDEF');
			}
			// Traverse languages:
			foreach ($lKeys as $lKey) {
				foreach ($sKeys as $sheet) {
					$sheetCfg = $dataStructArray['sheets'][$sheet];
					list($dataStruct, $sheet) = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveSheetDefInDS($dataStructArray, $sheet);
					// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el'])) {
						// Separate language key
						$lang = 'l' . $lKey;
						$PA['vKeys'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : array('DEF');
						$PA['lKey'] = $lang;
						$PA['callBackMethod_value'] = $callBackMethod_value;
						$PA['table'] = $table;
						$PA['field'] = $field;
						$PA['uid'] = $row['uid'];
						$this->traverseFlexFormXMLData_DS = &$dataStruct;
						$this->traverseFlexFormXMLData_Data = &$editData;
						// Render flexform:
						$this->traverseFlexFormXMLData_recurse($dataStruct['ROOT']['el'], $editData['data'][$sheet][$lang], $PA, 'data/' . $sheet . '/' . $lang);
					} else {
						return 'Data Structure ERROR: No ROOT element found for sheet "' . $sheet . '".';
					}
				}
			}
		} else {
			return 'Data Structure ERROR: ' . $dataStructArray;
		}
	}

	/**
	 * Recursively traversing flexform data according to data structure and element data
	 *
	 * @param array $dataStruct (Part of) data structure array that applies to the sub section of the flexform data we are processing
	 * @param array $editData (Part of) edit data array, reflecting current part of data structure
	 * @param array $PA Additional parameters passed.
	 * @param string $path Telling the "path" to the element in the flexform XML
	 * @return array
	 * @todo Define visibility
	 */
	public function traverseFlexFormXMLData_recurse($dataStruct, $editData, &$PA, $path = '') {
		if (is_array($dataStruct)) {
			foreach ($dataStruct as $key => $value) {
				// The value of each entry must be an array.
				if (is_array($value)) {
					if ($value['type'] == 'array') {
						// Array (Section) traversal
						if ($value['section']) {
							$cc = 0;
							if (is_array($editData[$key]['el'])) {
								if ($this->reNumberIndexesOfSectionData) {
									$temp = array();
									$c3 = 0;
									foreach ($editData[$key]['el'] as $v3) {
										$temp[++$c3] = $v3;
									}
									$editData[$key]['el'] = $temp;
								}
								foreach ($editData[$key]['el'] as $k3 => $v3) {
									if (is_array($v3)) {
										$cc = $k3;
										$theType = key($v3);
										$theDat = $v3[$theType];
										$newSectionEl = $value['el'][$theType];
										if (is_array($newSectionEl)) {
											$this->traverseFlexFormXMLData_recurse(array($theType => $newSectionEl), array($theType => $theDat), $PA, $path . '/' . $key . '/el/' . $cc);
										}
									}
								}
							}
						} else {
							// Array traversal:
							$this->traverseFlexFormXMLData_recurse($value['el'], $editData[$key]['el'], $PA, $path . '/' . $key . '/el');
						}
					} elseif (is_array($value['TCEforms']['config'])) {
						// Processing a field value:
						foreach ($PA['vKeys'] as $vKey) {
							$vKey = 'v' . $vKey;
							// Call back:
							if ($PA['callBackMethod_value']) {
								$this->callBackObj->{$PA['callBackMethod_value']}($value, $editData[$key][$vKey], $PA, $path . '/' . $key . '/' . $vKey, $this);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Returns an array of available languages to use for FlexForm operations
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function getAvailableLanguages() {
		$isL = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables');
		// Find all language records in the system
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('static_lang_isocode,title,uid', 'sys_language', 'pid=0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_language'), '', 'title');
		// Traverse them
		$output = array();
		$output[0] = array(
			'uid' => 0,
			'title' => 'Default language',
			'ISOcode' => 'DEF'
		);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$output[$row['uid']] = $row;
			if ($isL && $row['static_lang_isocode']) {
				$rr = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
				if ($rr['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $rr['lg_iso_2'];
				}
			}
			if (!$output[$row['uid']]['ISOcode']) {
				unset($output[$row['uid']]);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $output;
	}

	/***********************************
	 *
	 * Processing functions
	 *
	 ***********************************/
	/**
	 * Cleaning up FlexForm XML to hold only the values it may according to its Data Structure. Also the order of tags will follow that of the data structure.
	 * BE CAREFUL: DO not clean records in workspaces unless IN the workspace! The Data Structure might resolve falsely on a workspace record when cleaned from Live workspace.
	 *
	 * @param string $table Table name
	 * @param string $field Field name of the flex form field in which the XML is found that should be cleaned.
	 * @param array $row The record
	 * @return string Clean XML from FlexForm field
	 * @todo Define visibility
	 */
	public function cleanFlexFormXML($table, $field, $row) {
		// New structure:
		$this->cleanFlexFormXML = array();
		// Create and call iterator object:
		$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
		$flexObj->reNumberIndexesOfSectionData = TRUE;
		$flexObj->traverseFlexFormXMLData($table, $field, $row, $this, 'cleanFlexFormXML_callBackFunction');
		return $this->flexArray2Xml($this->cleanFlexFormXML, TRUE);
	}

	/**
	 * Call back function for \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools class
	 * Basically just setting the value in a new array (thus cleaning because only values that are valid are visited!)
	 *
	 * @param array $dsArr Data structure for the current value
	 * @param mixed $data Current value
	 * @param array $PA Additional configuration used in calling function
	 * @param string $path Path of value in DS structure
	 * @param object $pObj Object reference to caller
	 * @return void
	 * @todo Define visibility
	 */
	public function cleanFlexFormXML_callBackFunction($dsArr, $data, $PA, $path, $pObj) {
		// Just setting value in our own result array, basically replicating the structure:
		$pObj->setArrayValueByPath($path, $this->cleanFlexFormXML, $data);
		// Looking if an "extension" called ".vDEFbase" is found and if so, accept that too:
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase']) {
			$vDEFbase = $pObj->getArrayValueByPath($path . '.vDEFbase', $pObj->traverseFlexFormXMLData_Data);
			if (isset($vDEFbase)) {
				$pObj->setArrayValueByPath($path . '.vDEFbase', $this->cleanFlexFormXML, $vDEFbase);
			}
		}
	}

	/***********************************
	 *
	 * Multi purpose functions
	 *
	 ***********************************/
	/**
	 * Get a value from a multi-dimensional array by giving a path "../../.." pointing to the element
	 *
	 * @param string $pathArray The path pointing to the value field, eg. test/2/title to access $array['test'][2]['title']
	 * @param array $array Array to get value from. Passed by reference so the value returned can be used to change the value in the array!
	 * @return mixed Value returned
	 * @todo Define visibility
	 */
	public function &getArrayValueByPath($pathArray, &$array) {
		if (!is_array($pathArray)) {
			$pathArray = explode('/', $pathArray);
		}
		if (is_array($array)) {
			if (count($pathArray)) {
				$key = array_shift($pathArray);
				if (isset($array[$key])) {
					if (!count($pathArray)) {
						return $array[$key];
					} else {
						return $this->getArrayValueByPath($pathArray, $array[$key]);
					}
				} else {
					return NULL;
				}
			}
		}
	}

	/**
	 * Set a value in a multi-dimensional array by giving a path "../../.." pointing to the element
	 *
	 * @param string $pathArray The path pointing to the value field, eg. test/2/title to access $array['test'][2]['title']
	 * @param array $array Array to set value in. Passed by reference so the value returned can be used to change the value in the array!
	 * @param mixed $value Value to set
	 * @return mixed Value returned
	 * @todo Define visibility
	 */
	public function setArrayValueByPath($pathArray, &$array, $value) {
		if (isset($value)) {
			if (!is_array($pathArray)) {
				$pathArray = explode('/', $pathArray);
			}
			if (is_array($array)) {
				if (count($pathArray)) {
					$key = array_shift($pathArray);
					if (!count($pathArray)) {
						$array[$key] = $value;
						return TRUE;
					} else {
						if (!isset($array[$key])) {
							$array[$key] = array();
						}
						return $this->setArrayValueByPath($pathArray, $array[$key], $value);
					}
				}
			}
		}
	}

	/**
	 * Convert FlexForm data array to XML
	 *
	 * @param array $array Array to output in <T3FlexForms> XML
	 * @param boolean $addPrologue If set, the XML prologue is returned as well.
	 * @return string XML content.
	 * @todo Define visibility
	 */
	public function flexArray2Xml($array, $addPrologue = FALSE) {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexformForceCDATA']) {
			$this->flexArray2Xml_options['useCDATA'] = 1;
		}
		$options = $GLOBALS['TYPO3_CONF_VARS']['BE']['niceFlexFormXMLtags'] ? $this->flexArray2Xml_options : array();
		$spaceInd = $GLOBALS['TYPO3_CONF_VARS']['BE']['compactFlexFormXML'] ? -1 : 4;
		$output = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml($array, '', 0, 'T3FlexForms', $spaceInd, $options);
		if ($addPrologue) {
			$output = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . $output;
		}
		return $output;
	}

}


?>