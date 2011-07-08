<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Displays the secondary-options palette for the TCEFORMs wherever they are shown.
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @deprecated since TYPO3 4.5, this file will be removed in TYPO3 4.7.
 * 		The TYPO3 backend is using typo3/backend.php with less frames,
 * 		which makes this file obsolete.
 */


require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_alt_doc.xml');


t3lib_div::deprecationLog('alt_palette.php is deprecated since TYPO3 4.5, this file will be removed in TYPO3 4.7. The TYPO3 backend is using typo3/backend.php with less frames, which makes this file obsolete.');




/**
 * Class for rendering the form fields.
 * Extending the TCEforms class
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @deprecated since TYPO3 4.3, will be removed in TYPO3 4.6
 */
class formRender extends t3lib_TCEforms {

	/**
	 * Creates the HTML content for the palette
	 * (Horizontally, for display in the top frame)
	 * (Used if GET var "backRef" IS set)
	 *
	 * @deprecated since TYPO3 4.3, will be removed in TYPO3 4.6
	 * @param	array		Array of information from which the fields are built.
	 * @return	string		HTML output
	 */
	function printPalette($palArr)	{
		t3lib_div::logDeprecatedFunction();

		$out='';

			// For each element on the palette, write a few table cells with the field name, content and control images:
		foreach($palArr as $content)	{
			$iRow[]='
				<td>'.
					'<img name="req_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" class="c-reqIcon" src="clear.gif" width="10" height="10" alt="" />'.
					'<img name="cm_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" class="c-cmIcon" src="clear.gif" width="7" height="10" alt="" />'.
				'</td>
				<td class="c-label">'.
					$content['NAME'].'&nbsp;'.
				'</td>
				<td class="c-csh">'.
					$content['ITEM'].$content['HELP_ICON'].
				'</td>';
		}

			// Finally, wrap it all in a table:
		$out='



			<!--
				TCEforms palette, rendered in top frame.
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-TCEforms-palette">
				<tr>
					<td class="c-close">'.
					'<a href="#" onclick="closePal();return false;">' . t3lib_iconWorks::getSpriteIcon('actions-document-close', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.close', TRUE))) . '</a>'.
					'</td>'.
				implode('',$iRow).'
				</tr>
			</table>

			';

			// Return the result:
		return $out;
	}
}














/**
 * Child class for alternative rendering of form fields (when the secondary fields are shown in a little window rather than the top bar).
 * (Used if GET var "backRef" is not set, presuming a window is opened instead.)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class formRender_vert extends t3lib_TCEforms {

	/**
	 * Creates the HTML content for the palette.
	 * (Vertically, for display in a browser window, not top frame)
	 *
	 * @param	array		Array of information from which the fields are built.
	 * @return	string		HTML output
	 */
	function printPalette($palArr)	{
		$out='';
		$bgColor=' bgcolor="'.$this->colorScheme[2].'"';

			// For each element on the palette, write a few table cells with the field name, content and control images:
		foreach($palArr as $content)	{
			$iRow[]='
				<tr>
					<td><img src="clear.gif" width="'.intval($this->paletteMargin).'" height="1" alt="" /></td>
					<td'.$bgColor.'>&nbsp;</td>
					<td nowrap="nowrap"'.$bgColor.'><font color="'.$this->colorScheme[4].'">'.$content['NAME'].'</font></td>
				</tr>';
			$iRow[]='
				<tr>
					<td></td>
					<td valign="top"><img name="req_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="10" height="10" vspace="4" alt="" /><img name="cm_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="7" height="10" vspace="4" alt="" /></td>
					<td nowrap="nowrap" valign="top">'.$content['ITEM'].$content['HELP_ICON'].'</td>
				</tr>';
		}

			// Adding the close button:
		$iRow[]='
			<tr>
				<td></td>
				<td></td>
				<td nowrap="nowrap" valign="top">
					<br />
					<input type="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.close',1).'" onclick="closePal(); return false;" />
				</td>
			</tr>';

			// Finally, wrap it all in a table:
		$out='
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-TCEforms-palette-vert">
				'.implode('',$iRow).'
			</table>';

			// Return content:
		return $out;
	}
}











/**
 * Script Class for rendering the palette form for TCEforms in some other frame (in top frame, horizontally)
 * It can also be called in a pop-up window in which case a vertically oriented set of form fields are rendered instead.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_palette {

		// Internal:
	var $content;		// Content accumulation
	var $backRef;		// String, which is the reference back to the window which opened this one.
	var $formRef;		// String, which is the reference to the form.
	var $doc;			// Template object.

		// Internal, static: GPvar:
	var $formName;			// Form name
	var $GPbackref;			// The value of the original backRef GPvar (not necessarily the same as $this->backRef)
	var $inData;			// Contains tablename, uid and palette number
	var $prependFormFieldNames;		// Prefix for form fields.
	var $rec;				// The "record" with the data to display in the form fields.





	/**
	 * Constructor for the class
	 *
	 * @return	void
	 */
	function init()	{

			// Setting GPvars, etc.
		$this->formName = $this->sanitizeHtmlName(t3lib_div::_GP('formName'));
		$this->GPbackref = $this->sanitizeHtmlName(t3lib_div::_GP('backRef'));
		$this->inData = t3lib_div::_GP('inData');
			// safeguards the input with whitelisting
		if (!preg_match('/^[a-zA-Z0-9\-_\:]+$/', $this->inData)) {
			$this->inData = '';
		}
		$this->prependFormFieldNames =
			$this->sanitizeHtmlName(t3lib_div::_GP('prependFormFieldNames'));
		$this->rec = t3lib_div::_GP('rec');

			// Making references:
		$this->backRef = $this->GPbackref ? $this->GPbackref : 'window.opener';

		$this->formRef = $this->backRef.'.document.'.$this->formName;

			// Start template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->bodyTagMargins['x']=0;
		$this->doc->bodyTagMargins['y']=0;
		$this->doc->form='<form action="#" method="post" name="'.htmlspecialchars($this->formName).'" onsubmit="return false;">';
		$this->doc->backPath = '';

			// In case the palette is opened in a SEPARATE window (as the case is with frontend editing) then another body-tag id should be used (so we don't get the background image for the palette shown!)
		if (!$this->GPbackref)	$this->doc->bodyTagId.= '-vert';

			// Setting JavaScript functions for the header:
		$this->doc->JScode = $this->doc->wrapScriptTags('
			var serialNumber = "";
			function timeout_func()	{	//
				if ('.$this->backRef.' && '.$this->backRef.'.document && '.$this->formRef.')	{
					if ('.$this->formRef.'["_serialNumber"])	{
						if (serialNumber) {
							if ('.$this->formRef.'["_serialNumber"].value != serialNumber) {closePal(); return false;}
						} else {
							serialNumber = '.$this->formRef.'["_serialNumber"].value;
						}
					}
					window.setTimeout("timeout_func();",1*1000);
				} else closePal();
			}
			function closePal()	{	//
				'.($this->GPbackref?'window.location.href="alt_topmenu_dummy.php";':'close();').'
			}
			timeout_func();
			onBlur="alert();";
		');
	}

	/**
	 * Sanitizes HTML names, IDs, frame names etc.
	 *
	 * @param string $input the string to sanitize
	 *
	 * @return string the unchanged $input if $input is considered to be harmless,
	 *                an empty string otherwise
	 */
	protected function sanitizeHtmlName($input) {
		$result = $input;

		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\-\.]*$/', $result)) {
			$result = '';
		}

		return $result;
	}

	/**
	 * Main function, rendering the palette form
	 *
	 * @return	void
	 */
	function main()	{

		$this->content='';

		$inData = explode(':',$this->inData);

			// Begin edit:
		if (is_array($inData) && count($inData)==3)	{

				// Create the TCEforms object:
			$tceforms = $this->GPbackref ? new formRender() : new formRender_vert();
			$tceforms->initDefaultBEMode();
			$tceforms->palFieldTemplate='###FIELD_PALETTE###';
			$tceforms->palettesCollapsed=0;
			$tceforms->isPalettedoc=$this->backRef;

			$tceforms->formName = $this->formName;
			$tceforms->prependFormFieldNames = $this->prependFormFieldNames;

				// Initialize other data:
			$table=$inData[0];
			$theUid=$inData[1];
			$thePalNum = $inData[2];
			$this->rec['uid']=$theUid;

				// Getting the palette fields rendered:
			$panel.=$tceforms->getPaletteFields($table,$this->rec,$thePalNum,'',implode(',',array_keys($this->rec)));
			$formContent=$panel;

				// Add all the content, including JavaScript as needed.
			$this->content.=$tceforms->printNeededJSFunctions_top().$formContent.$tceforms->printNeededJSFunctions();
		}

		// Assemble the page:
		$tempContent = $this->content;
		$this->content = $this->doc->startPage('TYPO3 Edit Palette');
		$this->content.= $tempContent;
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_palette.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_palette.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_palette');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>