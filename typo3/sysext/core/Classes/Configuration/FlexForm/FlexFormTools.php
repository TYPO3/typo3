<?php
namespace TYPO3\CMS\Core\Configuration\FlexForm;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains functions for manipulating flex form data
 */
class FlexFormTools
{
    /**
     * If set, section indexes are re-numbered before processing
     *
     * @var bool
     */
    public $reNumberIndexesOfSectionData = false;

    /**
     * Contains data structure when traversing flexform
     *
     * @var array
     */
    public $traverseFlexFormXMLData_DS = array();

    /**
     * Contains data array when traversing flexform
     *
     * @var array
     */
    public $traverseFlexFormXMLData_Data = array();

    /**
     * Options for array2xml() for flexform.
     * This will map the weird keys from the internal array to tags that could potentially be checked with a DTD/schema
     *
     * @var array
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
     * @var object
     */
    public $callBackObj = null;

    /**
     * Used for accumulation of clean XML
     *
     * @var array
     */
    public $cleanFlexFormXML = array();

    /**
     * Handler for Flex Forms
     *
     * @param string $table The table name of the record
     * @param string $field The field name of the flexform field to work on
     * @param array $row The record data array
     * @param object $callBackObj Object in which the call back function is located
     * @param string $callBackMethod_value Method name of call back function in object for values
     * @return bool|string If TRUE, error happened (error string returned)
     */
    public function traverseFlexFormXMLData($table, $field, $row, $callBackObj, $callBackMethod_value)
    {
        if (!is_array($GLOBALS['TCA'][$table]) || !is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
            return 'TCA table/field was not defined.';
        }
        $this->callBackObj = $callBackObj;
        // Get Data Structure:
        $dataStructArray = BackendUtility::getFlexFormDS($GLOBALS['TCA'][$table]['columns'][$field]['config'], $row, $table, $field);
        // If data structure was ok, proceed:
        if (is_array($dataStructArray)) {
            // Get flexform XML data
            $editData = GeneralUtility::xml2array($row[$field]);
            if (!is_array($editData)) {
                return 'Parsing error: ' . $editData;
            }
            // Tabs sheets
            if (is_array($dataStructArray['sheets'])) {
                $sKeys = array_keys($dataStructArray['sheets']);
            } else {
                $sKeys = array('sDEF');
            }
            // Traverse languages:
            foreach ($sKeys as $sheet) {
                list($dataStruct, $sheet) = GeneralUtility::resolveSheetDefInDS($dataStructArray, $sheet);
                // Render sheet:
                if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el'])) {
                    $PA['vKeys'] = array('DEF');
                    $PA['lKey'] = 'lDEF';
                    $PA['callBackMethod_value'] = $callBackMethod_value;
                    $PA['table'] = $table;
                    $PA['field'] = $field;
                    $PA['uid'] = $row['uid'];
                    $this->traverseFlexFormXMLData_DS = &$dataStruct;
                    $this->traverseFlexFormXMLData_Data = &$editData;
                    // Render flexform:
                    $this->traverseFlexFormXMLData_recurse($dataStruct['ROOT']['el'], $editData['data'][$sheet]['lDEF'], $PA, 'data/' . $sheet . '/lDEF');
                } else {
                    return 'Data Structure ERROR: No ROOT element found for sheet "' . $sheet . '".';
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
     */
    public function traverseFlexFormXMLData_recurse($dataStruct, $editData, &$PA, $path = '')
    {
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
                            // Array traversal
                            if (is_array($editData) && is_array($editData[$key])) {
                                $this->traverseFlexFormXMLData_recurse($value['el'], $editData[$key]['el'], $PA, $path . '/' . $key . '/el');
                            }
                        }
                    } elseif (is_array($value['TCEforms']['config'])) {
                        // Processing a field value:
                        foreach ($PA['vKeys'] as $vKey) {
                            $vKey = 'v' . $vKey;
                            // Call back
                            if ($PA['callBackMethod_value'] && is_array($editData) && is_array($editData[$key])) {
                                $this->executeCallBackMethod($PA['callBackMethod_value'], array($value, $editData[$key][$vKey], $PA, $path . '/' . $key . '/' . $vKey, $this));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Execute method on callback object
     *
     * @param string $methodName Method name to call
     * @param array $parameterArray Parameters
     * @return mixed Result of callback object
     */
    protected function executeCallBackMethod($methodName, array $parameterArray)
    {
        return call_user_func_array(array($this->callBackObj, $methodName), $parameterArray);
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
     */
    public function cleanFlexFormXML($table, $field, $row)
    {
        // New structure:
        $this->cleanFlexFormXML = array();
        // Create and call iterator object:
        $flexObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
        $flexObj->reNumberIndexesOfSectionData = true;
        $flexObj->traverseFlexFormXMLData($table, $field, $row, $this, 'cleanFlexFormXML_callBackFunction');
        return $this->flexArray2Xml($this->cleanFlexFormXML, true);
    }

    /**
     * Call back function for \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools class
     * Basically just setting the value in a new array (thus cleaning because only values that are valid are visited!)
     *
     * @param array $dsArr Data structure for the current value
     * @param mixed $data Current value
     * @param array $PA Additional configuration used in calling function
     * @param string $path Path of value in DS structure
     * @param FlexFormTools $pObj caller
     * @return void
     */
    public function cleanFlexFormXML_callBackFunction($dsArr, $data, $PA, $path, $pObj)
    {
        // Just setting value in our own result array, basically replicating the structure:
        $pObj->setArrayValueByPath($path, $this->cleanFlexFormXML, $data);
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
     */
    public function &getArrayValueByPath($pathArray, &$array)
    {
        if (!is_array($pathArray)) {
            $pathArray = explode('/', $pathArray);
        }
        if (is_array($array) && !empty($pathArray)) {
            $key = array_shift($pathArray);
            if (isset($array[$key])) {
                if (empty($pathArray)) {
                    return $array[$key];
                }
                return $this->getArrayValueByPath($pathArray, $array[$key]);
            }
            return null;
        }
    }

    /**
     * Set a value in a multi-dimensional array by giving a path "../../.." pointing to the element
     *
     * @param string $pathArray The path pointing to the value field, eg. test/2/title to access $array['test'][2]['title']
     * @param array $array Array to set value in. Passed by reference so the value returned can be used to change the value in the array!
     * @param mixed $value Value to set
     * @return mixed Value returned
     */
    public function setArrayValueByPath($pathArray, &$array, $value)
    {
        if (isset($value)) {
            if (!is_array($pathArray)) {
                $pathArray = explode('/', $pathArray);
            }
            if (is_array($array) && !empty($pathArray)) {
                $key = array_shift($pathArray);
                if (empty($pathArray)) {
                    $array[$key] = $value;
                    return true;
                }
                if (!isset($array[$key])) {
                    $array[$key] = array();
                }
                return $this->setArrayValueByPath($pathArray, $array[$key], $value);
            }
        }
    }

    /**
     * Convert FlexForm data array to XML
     *
     * @param array $array Array to output in <T3FlexForms> XML
     * @param bool $addPrologue If set, the XML prologue is returned as well.
     * @return string XML content.
     */
    public function flexArray2Xml($array, $addPrologue = false)
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexformForceCDATA']) {
            $this->flexArray2Xml_options['useCDATA'] = 1;
        }
        $output = GeneralUtility::array2xml($array, '', 0, 'T3FlexForms', 4, $this->flexArray2Xml_options);
        if ($addPrologue) {
            $output = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . $output;
        }
        return $output;
    }
}
