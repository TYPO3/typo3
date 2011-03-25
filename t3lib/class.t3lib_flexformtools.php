<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   71: class t3lib_flexformtools
 *  105:	 function traverseFlexFormXMLData($table,$field,$row,&$callBackObj,$callBackMethod_value)
 *  203:	 function traverseFlexFormXMLData_recurse($dataStruct,$editData,&$PA,$path='')
 *  274:	 function getAvailableLanguages()
 *
 *			  SECTION: Processing functions
 *  323:	 function cleanFlexFormXML($table,$field,$row)
 *  347:	 function cleanFlexFormXML_callBackFunction($dsArr, $data, $PA, $path, &$pObj)
 *
 *			  SECTION: Multi purpose functions
 *  374:	 function &getArrayValueByPath($pathArray,&$array)
 *  403:	 function setArrayValueByPath($pathArray,&$array,$value)
 *  433:	 function flexArray2Xml($array, $addPrologue=FALSE)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Contains functions for manipulating flex form data
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_flexformtools {

	var $convertCharset = FALSE; // If set, the charset of data XML is converted to system charset.
	var $reNumberIndexesOfSectionData = FALSE; // If set, section indexes are re-numbered before processing

	var $traverseFlexFormXMLData_DS = array(); // Contains data structure when traversing flexform
	var $traverseFlexFormXMLData_Data = array(); // Contains data array when traversing flexform

		// Options for array2xml() for flexform. This will map the weird keys from the internal array to tags that could potentially be checked with a DTD/schema
	var $flexArray2Xml_options = array(
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

		// Internal:
	/**
	 * Reference to object called
	 */
	var $callBackObj = NULL;
	var $cleanFlexFormXML = array(); // Used for accumulation of clean XML

	/**
	 * Handler for Flex Forms
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name of the flexform field to work on
	 * @param	array		The record data array
	 * @param	object		Object (passed by reference) in which the call back function is located
	 * @param	string		Method name of call back function in object for values
	 * @return	boolean		If true, error happened (error string returned)
	 */
	function traverseFlexFormXMLData($table, $field, $row, $callBackObj, $callBackMethod_value) {

		if (!is_array($GLOBALS['TCA'][$table]) || !is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
			return 'TCA table/field was not defined.';
		}

		$this->callBackObj = $callBackObj;

			// Get Data Structure:
		$dataStructArray = t3lib_BEfunc::getFlexFormDS($GLOBALS['TCA'][$table]['columns'][$field]['config'], $row, $table);

			// If data structure was ok, proceed:
		if (is_array($dataStructArray)) {

				// Get flexform XML data:
			$xmlData = $row[$field];

				// Convert charset:
			if ($this->convertCharset) {
				$xmlHeaderAttributes = t3lib_div::xmlGetHeaderAttribs($xmlData);
				$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
				if ($storeInCharset) {
					$currentCharset = $GLOBALS['LANG']->charSet;
					$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData, $storeInCharset, $currentCharset, 1);
				}
			}

			$editData = t3lib_div::xml2array($xmlData);
			if (!is_array($editData)) {
				return 'Parsing error: ' . $editData;
			}

				// Language settings:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;

				// empty or invalid <meta>
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
					list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray, $sheet);

						// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el'])) {
						$lang = 'l' . $lKey; // Separate language key
						$PA['vKeys'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : array('DEF');
						$PA['lKey'] = $lang;
						$PA['callBackMethod_value'] = $callBackMethod_value;
						$PA['table'] = $table;
						$PA['field'] = $field;
						$PA['uid'] = $row['uid'];

						$this->traverseFlexFormXMLData_DS = &$dataStruct;
						$this->traverseFlexFormXMLData_Data = &$editData;

							// Render flexform:
						$this->traverseFlexFormXMLData_recurse(
							$dataStruct['ROOT']['el'],
							$editData['data'][$sheet][$lang],
							$PA,
								'data/' . $sheet . '/' . $lang
						);
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
	 * @param	array		(Part of) data structure array that applies to the sub section of the flexform data we are processing
	 * @param	array		(Part of) edit data array, reflecting current part of data structure
	 * @param	array		Additional parameters passed.
	 * @param	string		Telling the "path" to the element in the flexform XML
	 * @return	array
	 */
	function traverseFlexFormXMLData_recurse($dataStruct, $editData, &$PA, $path = '') {

		if (is_array($dataStruct)) {
			foreach ($dataStruct as $key => $value) {
				if (is_array($value)) { // The value of each entry must be an array.

					if ($value['type'] == 'array') {
						if ($value['section']) { // Array (Section) traversal:

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
											$this->traverseFlexFormXMLData_recurse(
												array($theType => $newSectionEl),
												array($theType => $theDat),
												$PA,
													$path . '/' . $key . '/el/' . $cc
											);
										}
									}
								}
							}
						} else { // Array traversal:
							$this->traverseFlexFormXMLData_recurse(
								$value['el'],
								$editData[$key]['el'],
								$PA,
									$path . '/' . $key . '/el'
							);
						}
					} elseif (is_array($value['TCEforms']['config'])) { // Processing a field value:

						foreach ($PA['vKeys'] as $vKey) {
							$vKey = 'v' . $vKey;

								// Call back:
							if ($PA['callBackMethod_value']) {
								$this->callBackObj->$PA['callBackMethod_value'](
									$value,
									$editData[$key][$vKey],
									$PA,
										$path . '/' . $key . '/' . $vKey,
									$this
								);
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
	 * @return	array
	 */
	function getAvailableLanguages() {
		$isL = t3lib_extMgm::isLoaded('static_info_tables');

			// Find all language records in the system:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('static_lang_isocode,title,uid', 'sys_language', 'pid=0' . t3lib_BEfunc::deleteClause('sys_language'), '', 'title');

			// Traverse them:
		$output = array();
		$output[0] = array(
			'uid' => 0,
			'title' => 'Default language',
			'ISOcode' => 'DEF'
		);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$output[$row['uid']] = $row;

			if ($isL && $row['static_lang_isocode']) {
				$rr = t3lib_BEfunc::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
				if ($rr['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $rr['lg_iso_2'];
				}
			}

			if (!$output[$row['uid']]['ISOcode']) {
				unset($output[$row['uid']]);
			}
		}
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
	 * @param	string		Table name
	 * @param	string		Field name of the flex form field in which the XML is found that should be cleaned.
	 * @param	array		The record
	 * @return	string		Clean XML from FlexForm field
	 */
	function cleanFlexFormXML($table, $field, $row) {

			// New structure:
		$this->cleanFlexFormXML = array();

			// Create and call iterator object:
		$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
		$flexObj->reNumberIndexesOfSectionData = TRUE;
		$flexObj->traverseFlexFormXMLData($table, $field, $row, $this, 'cleanFlexFormXML_callBackFunction');

		return $this->flexArray2Xml($this->cleanFlexFormXML, TRUE);
	}

	/**
	 * Call back function for t3lib_flexformtools class
	 * Basically just setting the value in a new array (thus cleaning because only values that are valid are visited!)
	 *
	 * @param	array		Data structure for the current value
	 * @param	mixed		Current value
	 * @param	array		Additional configuration used in calling function
	 * @param	string		Path of value in DS structure
	 * @param	object		Object reference to caller
	 * @return	void
	 */
	function cleanFlexFormXML_callBackFunction($dsArr, $data, $PA, $path, $pObj) {
		#debug(array($dsArr, $data, $PA),$path);
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
	 * @param	string		The path pointing to the value field, eg. test/2/title to access $array['test'][2]['title']
	 * @param	array		Array to get value from. Passed by reference so the value returned can be used to change the value in the array!
	 * @return	mixed		Value returned
	 */
	function &getArrayValueByPath($pathArray, &$array) {
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
	 * @param	string		The path pointing to the value field, eg. test/2/title to access $array['test'][2]['title']
	 * @param	array		Array to set value in. Passed by reference so the value returned can be used to change the value in the array!
	 * @param	mixed		Value to set
	 * @return	mixed		Value returned
	 */
	function setArrayValueByPath($pathArray, &$array, $value) {
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
	 * @param	array		Array to output in <T3FlexForms> XML
	 * @param	boolean		If set, the XML prologue is returned as well.
	 * @return	string		XML content.
	 */
	function flexArray2Xml($array, $addPrologue = FALSE) {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexformForceCDATA']) {
			$this->flexArray2Xml_options['useCDATA'] = 1;
		}

		$options = $GLOBALS['TYPO3_CONF_VARS']['BE']['niceFlexFormXMLtags'] ? $this->flexArray2Xml_options : array();
		$spaceInd = ($GLOBALS['TYPO3_CONF_VARS']['BE']['compactFlexFormXML'] ? -1 : 4);
		$output = t3lib_div::array2xml($array, '', 0, 'T3FlexForms', $spaceInd, $options);

		if ($addPrologue) {
			$output = '<?xml version="1.0" encoding="' . $GLOBALS['LANG']->charSet . '" standalone="yes" ?>' . LF . $output;
		}

		return $output;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flexformtools.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flexformtools.php']);
}
?>