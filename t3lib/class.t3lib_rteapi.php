<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * RTE API parent class.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   64: class t3lib_rteapi
 *
 *              SECTION: Main API functions;
 *   93:     function isAvailable()
 *  118:     function drawRTE(&$pObj,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue)
 *  151:     function transformContent($dirRTE,$value,$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$pid)
 *
 *              SECTION: Helper functions
 *  197:     function triggerField($fieldName)
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */





/**
 * RTE base class: Delivers browser-detection, TCEforms binding and transformation routines for the "rte" extension, registering it with the RTE API in TYPO3 3.6.0
 * See "rte" extension for usage.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_rteapi {

		// Internal, dynamic:
	var $errorLog = array();		// Error messages regarding non-availability is collected here.

		// Internal, static:
	var $ID = '';					// Set this to the extension key of the RTE so it can identify itself.








	/***********************************
	 *
	 * Main API functions;
	 * When you create alternative RTEs, simply override these functions in your parent class.
	 * See the "rte" or "rtehtmlarea" extension as an example!
	 *
	 **********************************/

	/**
	 * Returns true if the RTE is available. Here you check if the browser requirements are met.
	 * If there are reasons why the RTE cannot be displayed you simply enter them as text in ->errorLog
	 *
	 * @return	boolean		TRUE if this RTE object offers an RTE in the current browser environment
	 */
	function isAvailable()	{
		global $CLIENT;

		$this->errorLog = array();
		if (!$CLIENT['FORMSTYLE']) 	$this->errorLog[] = 'RTE API: Browser didn\'t support styles';

		if (!count($this->errorLog))	return TRUE;
	}

	/**
	 * Draws the RTE as a form field or whatever is needed (inserts JavaApplet, creates iframe, renders ....)
	 * Default is to output the transformed content in a plain textarea field. This mode is great for debugging transformations!
	 *
	 * @param	object		Reference to parent object, which is an instance of the TCEforms.
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The current row from which field is being rendered
	 * @param	array		Array of standard content for rendering form fields from TCEforms. See TCEforms for details on this. Includes for instance the value and the form field name, java script actions and more.
	 * @param	array		"special" configuration - what is found at position 4 in the types configuration of a field from record, parsed into an array.
	 * @param	array		Configuration for RTEs; A mix between TSconfig and otherwise. Contains configuration for display, which buttons are enabled, additional transformation information etc.
	 * @param	string		Record "type" field value.
	 * @param	string		Relative path for images/links in RTE; this is used when the RTE edits content from static files where the path of such media has to be transformed forth and back!
	 * @param	integer		PID value of record (true parent page id)
	 * @return	string		HTML code for RTE!
	 */
	function drawRTE(&$pObj,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue)	{

			// Transform value:
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$thePidValue);

			// Create item:
		$item = '
			'.$this->triggerField($PA['itemFormElName']).'
			<textarea name="'.htmlspecialchars($PA['itemFormElName']).'"'.$pObj->formWidthText('48','off').' rows="20" wrap="off" style="background-color: #99eebb;">'.
			t3lib_div::formatForTextarea($value).
			'</textarea>';

			// Return form item:
		return $item;
	}

	/**
	 * Performs transformation of content to/from RTE. The keyword $dirRTE determines the direction.
	 * This function is called in two situations:
	 * a) Right before content from database is sent to the RTE (see ->drawRTE()) it might need transformation
	 * b) When content is sent from the RTE and into the database it might need transformation back again (going on in TCEmain class; You can't affect that.)
	 *
	 * @param	string		Keyword: "rte" means direction from db to rte, "db" means direction from Rte to DB
	 * @param	string		Value to transform.
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The current row from which field is being rendered
	 * @param	array		"special" configuration - what is found at position 4 in the types configuration of a field from record, parsed into an array.
	 * @param	array		Configuration for RTEs; A mix between TSconfig and otherwise. Contains configuration for display, which buttons are enabled, additional transformation information etc.
	 * @param	string		Relative path for images/links in RTE; this is used when the RTE edits content from static files where the path of such media has to be transformed forth and back!
	 * @param	integer		PID value of record (true parent page id)
	 * @return	string		Transformed content
	 */
	function transformContent($dirRTE,$value,$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$pid)	{

#debug(array($dirRTE,$value,$table,$field,array(),$specConf,$thisConfig,$RTErelPath,$pid));

		if ($specConf['rte_transform'])	{
			$p = t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
			if ($p['mode'])	{	// There must be a mode set for transformation
#debug($p['mode'],'MODE');

					// Initialize transformation:
				$parseHTML = t3lib_div::makeInstance('t3lib_parsehtml_proc');
				$parseHTML->init($table.':'.$field, $pid);
				$parseHTML->setRelPath($RTErelPath);

					// Perform transformation:
				$value = $parseHTML->RTE_transform($value, $specConf, $dirRTE, $thisConfig);
			}
		}

#debug(array($dirRTE,$value),'OUT: '.$dirRTE);
		return $value;
	}












	/***********************************
	 *
	 * Helper functions
	 *
	 **********************************/

	/**
	 * Trigger field - this field tells the TCEmain that processing should be done on this value!
	 *
	 * @param	string		Field name of the RTE field.
	 * @return	string		<input> field of type "hidden" with a flag telling the TCEmain that this fields content should be traansformed back to database state.
	 */
	function triggerField($fieldName)	{

		$triggerFieldName = ereg_replace('\[([^]]+)\]$','[_TRANSFORM_\1]', $fieldName);
		return '<input type="hidden" name="'.htmlspecialchars($triggerFieldName).'" value="RTE" />';
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rte/class.tx_rte_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rte/class.tx_rte_base.php']);
}
?>
