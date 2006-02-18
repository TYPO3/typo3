<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   67: class t3lib_flexformtools
 *   85:     function traverseFlexFormXMLData($table,$field,$row,&$callBackObj,$callBackMethod_value)
 *  206:     function traverseFlexFormXMLData_recurse($dataStruct,$editData,$table,$field,&$PA,$path='')
 *  298:     function getAvailableLanguages()
 *
 *              SECTION: Multi purpose functions
 *  346:     function &getArrayValueByPath($pathArray,&$array)
 *  375:     function setArrayValueByPath($pathArray,&$array,$value)
 *  402:     function flexArray2Xml($array)
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */







/**
 * Contains functions for manipulating flex form data
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_flexformtools {

	var $convertCharset = FALSE;		// If set, the charset of data XML is converted to system charset.
	var $reNumberIndexesOfSectionData = FALSE;	// If set, section indexes are re-numbered before processing

		// Internal:
	var $callBackObj = NULL;		// Reference to object called

	/**
	 * Handler for Flex Forms
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name of the flexform field to work on
	 * @param	array		The record data array
	 * @param	string		Method name of call back function in object for values
	 * @param	[type]		$callBackMethod_value: ...
	 * @return	string
	 * @poram	object		Object (passed by reference) in which the call back function is located
	 */
	function traverseFlexFormXMLData($table,$field,$row,&$callBackObj,$callBackMethod_value)	{

		$this->callBackObj = &$callBackObj;

			// Get Data Structure:
		$dataStructArray = t3lib_BEfunc::getFlexFormDS($GLOBALS['TCA'][$table]['columns'][$field]['config'],$row,$table);

			// If data structure was ok, proceed:
		if (is_array($dataStructArray))	{

				// Get flexform XML data:
			$xmlData = $row[$field];

				// Convert charset:
			if ($this->convertCharset)	{
				$xmlHeaderAttributes = t3lib_div::xmlGetHeaderAttribs($xmlData);
				$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
				if ($storeInCharset)	{
					$currentCharset = $GLOBALS['LANG']->charSet;
					$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData,$storeInCharset,$currentCharset,1);
				}
			}

			$editData = t3lib_div::xml2array($xmlData);
			if (!is_array($editData))	{	// Must be XML parsing error...
				$editData = array();
			}

				// Language settings:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;

			$editData['meta']['currentLangId'] = array();
			$languages = $this->getAvailableLanguages();

			foreach($languages as $lInfo)	{
				$editData['meta']['currentLangId'][] = $lInfo['ISOcode'];
			}
			if (!count($editData['meta']['currentLangId']))	{
				$editData['meta']['currentLangId'] = array('DEF');
			}

			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);

			if ($langChildren || $langDisabled)	{
				$rotateLang = array('DEF');
			} else {
				$rotateLang = $editData['meta']['currentLangId'];
			}

				// Tabs sheets
			if (is_array($dataStructArray['sheets']))	{
				$tabsToTraverse = array_keys($dataStructArray['sheets']);
			} else {
				$tabsToTraverse = array('sDEF');
			}

				// Traverse languages:
			foreach($rotateLang as $lKey)	{
				if (!$langChildren && !$langDisabled)	{
					$item.= '<b>'.$lKey.':</b>';
				}

				$tabParts = array();
				foreach($tabsToTraverse as $sheet)	{
					$sheetCfg = $dataStructArray['sheets'][$sheet];
					list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sheet);

						// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el']))		{
						$lang = 'l'.$lKey;	// Separate language key
						$PA['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF';	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_lang'] = $lang;
						$PA['callBackMethod_value'] = $callBackMethod_value;

							// Render flexform:
						$sheetOutput = $this->traverseFlexFormXMLData_recurse(
							$dataStruct['ROOT']['el'],
							$editData['data'][$sheet][$lang],
							$table,
							$field,
							$PA,
							'data/'.$sheet.'/'.$lang
						);
						$sheetContent= t3lib_div::view_array($sheetOutput);

					} else $sheetContent='Data Structure ERROR: No ROOT element found for sheet "'.$sheet.'".';

						// Add to tab:
					$tabParts[] = array(
						'label' => ($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sheet),
						'description' => ($sheetCfg['ROOT']['TCEforms']['sheetDescription'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetDescription']) : ''),
						'linkTitle' => ($sheetCfg['ROOT']['TCEforms']['sheetShortDescr'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetShortDescr']) : ''),
						'content' => $sheetContent
					);
				}

				if (is_array($dataStructArray['sheets']))	{
					$item.= implode('<br/>',$tabParts).'<hr/>';
				} else {
					$item.= $sheetContent;
				}
			}

		} else $item = 'Data Structure ERROR: '.$dataStructArray;

		return $item;
	}


	/**
	 * Recursively traversing flexform data according to data structure and element data
	 *
	 * @param	array		(Part of) data structure array that applies to the sub section of the flexform data we are processing
	 * @param	array		(Part of) edit data array, reflecting current part of data structure
	 * @param	string		Table name
	 * @param	string		Field name (of flexform field)
	 * @param	array		Additional parameters passed.
	 * @param	string		Telling the "path" to the element in the flexform XML
	 * @return	array
	 */
	function traverseFlexFormXMLData_recurse($dataStruct,$editData,$table,$field,&$PA,$path='')	{

			// Data Structure array must be ... and array of course...
		$levelOutput = array();

		if (is_array($dataStruct))	{
			foreach($dataStruct as $key => $value)	{
				if (is_array($value))	{	// The value of each entry must be an array.

					$levelOutput[$key] = array();
					$levelOutput[$key]['path'] = $path;
					$levelOutput[$key]['title'] = $value['tx_templavoila']['title'];

					if ($value['type']=='array')	{
						if ($value['section'])	{
							$levelOutput[$key]['type'] = 'array/section';

							$cc = 0;
							if (is_array($editData[$key]['el']))	{
								
								if ($this->reNumberIndexesOfSectionData)	{
									$temp = array();
									$c3=0;
									foreach($editData[$key]['el'] as $v3)	{
										$temp[++$c3] = $v3;
									}
									$editData[$key]['el'] = $temp;
								}
								
								foreach($editData[$key]['el'] as $k3 => $v3)	{
									$cc=$k3;
									$theType = key($v3);
									$theDat = $v3[$theType];
									$newSectionEl = $value['el'][$theType];
									if (is_array($newSectionEl))	{
										$levelOutput[$key]['el'][$k3] = $this->traverseFlexFormXMLData_recurse(
											array($theType => $newSectionEl),
											array($theType => $theDat),
											$table,
											$field,
											$PA,
											$path.'/'.$key.'/el/'.$cc
										);
									}
								}
							}
						} else {
							$levelOutput[$key]['type'] = 'array';

							$levelOutput[$key]['el'] = $this->traverseFlexFormXMLData_recurse(
								$value['el'],
								$editData[$key]['el'],
								$table,
								$field,
								$PA,
								$path.'/'.$key.'/el'
							);
						}

					} elseif (is_array($value['TCEforms']['config'])) {	// Rendering a single form element:
						$levelOutput[$key]['path'] = $path;
						$levelOutput[$key]['title'] = $value['TCEforms']['config']['label'];
						$levelOutput[$key]['type'] = 'element';

						if (is_array($PA['_valLang']))	{
							$rotateLang = $PA['_valLang'];
						} else {
							$rotateLang = array($PA['_valLang']);
						}

						foreach($rotateLang as $vDEFkey)	{
							$vDEFkey = 'v'.$vDEFkey;

							$levelOutput[$key]['el'][$vDEFkey] = array();

							if(isset($editData[$key][$vDEFkey])) {
								$levelOutput[$key]['el'][$vDEFkey]['value'] = $editData[$key][$vDEFkey];
							}

								// Call back:
							if ($PA['callBackMethod_value'])	{
								$this->callBackObj->$PA['callBackMethod_value'](
									$value,
									$editData[$key][$vDEFkey],
									$PA,
									$path.'/'.$key.'/'.$vDEFkey,
									$this
								);
							}
						}
					}
				}
			}
		}

		return $levelOutput;
	}

	/**
	 * Returns an array of available languages to use for FlexForm operations
	 *
	 * @return	array
	 */
	function getAvailableLanguages()	{
		$isL = t3lib_extMgm::isLoaded('static_info_tables');

			// Find all language records in the system:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('static_lang_isocode,title,uid', 'sys_language', 'pid=0'.t3lib_BEfunc::deleteClause('sys_language'), '', 'title');

			// Traverse them:
		$output = array();
		$output[0]=array(
			'uid' => 0,
			'title' => 'Default language',
			'ISOcode' => 'DEF'
		);

		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$output[$row['uid']] = $row;

			if ($isL && $row['static_lang_isocode'])	{
				$rr = t3lib_BEfunc::getRecord('static_languages',$row['static_lang_isocode'],'lg_iso_2');
				if ($rr['lg_iso_2'])	$output[$row['uid']]['ISOcode']=$rr['lg_iso_2'];
			}

			if (!$output[$row['uid']]['ISOcode'])	unset($output[$row['uid']]);
		}
		return $output;
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
	function &getArrayValueByPath($pathArray,&$array)	{
		if (!is_array($pathArray))	{
			$pathArray = explode('/',$pathArray);
		}
		if (is_array($array))	{
			if (count($pathArray))	{
				$key = array_shift($pathArray);

				if (isset($array[$key]))	{
					if (!count($pathArray))	{
						return $array[$key];
					} else {
						return $this->getArrayValueByPath($pathArray,$array[$key]);
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
	function setArrayValueByPath($pathArray,&$array,$value)	{
		if (isset($value))	 {		
			if (!is_array($pathArray))	{
				$pathArray = explode('/',$pathArray);
			}
			if (is_array($array))	{
				if (count($pathArray))	{
					$key = array_shift($pathArray);
	
					if (!count($pathArray))	{
						$array[$key] = $value;
						return TRUE;
					} else {
						if (!isset($array[$key]))	{
							$array[$key] = array();
						}
						return $this->setArrayValueByPath($pathArray,$array[$key],$value);
					}
				}
			}
		}
	}

	/**
	 * Convert FlexForm data array to XML
	 *
	 * @param	array		Array
	 * @return	string		XML
	 */
	function flexArray2Xml($array)	{
		$output = t3lib_div::array2xml($array,'',0,'T3FlexForms',4,array('parentTagMap' => array(
					'data' => 'sheet',
					'sheet' => 'language',
					'language' => 'field',
					'el' => 'field'
				)));
		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flexformtools.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_flexformtools.php']);
}
?>