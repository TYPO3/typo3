<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Colorpicker wizard
 *
 * $Id $
 * Revised for TYPO3 3.7 May/2004 by Kasper Skaarhoj
 *
 * @author	Mathias Schreiber <schreiber@wmdb.de>
 * @author	Peter Kühn <peter@kuehn.com>
 * @author	Kasper Skaarhoj <typo3@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   75: class SC_wizard_colorpicker
 *  103:     function init()
 *  182:     function main()
 *  233:     function printContent()
 *  246:     function frameSet()
 *
 *              SECTION: Rendering of various color selectors
 *  305:     function colorMatrix()
 *  354:     function colorList()
 *  384:     function colorImage()
 *  417:     function getIndex($im,$x,$y)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$BACK_PATH = '';
require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_wizards.xml');

/**
 * Script Class for colorpicker wizard
 *
 * @author	Mathias Schreiber <schreiber@wmdb.de>
 * @author	Peter Kühn <peter@kuehn.com>
 * @author	Kasper Skaarhoj <typo3@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_colorpicker {

		// GET vars:
	var $P;				// Wizard parameters, coming from TCEforms linking to the wizard.
	var $colorValue;	// Value of the current color picked.
	var $fieldChangeFunc;	// Serialized functions for changing the field... Necessary to call when the value is transferred to the TCEform since the form might need to do internal processing. Otherwise the value is simply not be saved.
	var $fieldName;		// Form name (from opener script)
	var $formName;		// Field name (from opener script)
	var $md5ID;			// ID of element in opener script for which to set color.
	var $showPicker;	// Internal: If false, a frameset is rendered, if true the content of the picker script.

		// Static:
	var $HTMLcolorList = "aqua,black,blue,fuchsia,gray,green,lime,maroon,navy,olive,purple,red,silver,teal,yellow,white";

		// Internal:
	var $pickerImage = '';
	var $imageError = '';		// Error message if image not found.

	/**
	 * document template object
	 *
	 * @var smallDoc
	 */
	var $doc;
	var $content;				// Accumulated content.




	/**
	 * Initialises the Class
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH, $LANG;

			// Setting GET vars (used in frameset script):
		$this->P = t3lib_div::_GP('P',1);

			// Setting GET vars (used in colorpicker script):
		$this->colorValue = t3lib_div::_GP('colorValue');
		$this->fieldChangeFunc = t3lib_div::_GP('fieldChangeFunc');
		$this->fieldName = t3lib_div::_GP('fieldName');
		$this->formName = t3lib_div::_GP('formName');
		$this->md5ID = t3lib_div::_GP('md5ID');
		$this->exampleImg = t3lib_div::_GP('exampleImg');


			// Resolving image (checking existence etc.)
		$this->imageError = '';
		if ($this->exampleImg)	{
			$this->pickerImage = t3lib_div::getFileAbsFileName($this->exampleImg,1,1);
			if (!$this->pickerImage || !@is_file($this->pickerImage))	{
				$this->imageError = 'ERROR: The image, "'.$this->exampleImg.'", could not be found!';
			}
		}

			// Setting field-change functions:
		$fieldChangeFuncArr = unserialize($this->fieldChangeFunc);
		$update = '';
		if (is_array($fieldChangeFuncArr))	{
			unset($fieldChangeFuncArr['alert']);
			foreach($fieldChangeFuncArr as $v)	{
				$update.= '
				parent.opener.'.$v;
			}
		}

			// Initialize document object:
		$this->doc = t3lib_div::makeInstance('smallDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function checkReference()	{	//
				if (parent.opener && parent.opener.document && parent.opener.document.'.$this->formName.' && parent.opener.document.'.$this->formName.'["'.$this->fieldName.'"])	{
					return parent.opener.document.'.$this->formName.'["'.$this->fieldName.'"];
				} else {
					close();
				}
			}
			function changeBGcolor(color) {	// Changes the color in the table sample back in the TCEform.
			    if (parent.opener.document.layers)	{
			        parent.opener.document.layers["'.$this->md5ID.'"].bgColor = color;
			    } else if (parent.opener.document.all)	{
			        parent.opener.document.all["'.$this->md5ID.'"].style.background = color;
				} else if (parent.opener.document.getElementById && parent.opener.document.getElementById("'.$this->md5ID.'"))	{
					parent.opener.document.getElementById("'.$this->md5ID.'").bgColor = color;
				}
			}
			function setValue(input)	{	//
				var field = checkReference();
				if (field)	{
					field.value = input;
					'.$update.'
					changeBGcolor(input);
				}
			}
			function getValue()	{	//
				var field = checkReference();
				return field.value;
			}
		');

			// Start page:
		$this->content.=$this->doc->startPage($LANG->getLL('colorpicker_title'));
	}

	/**
	 * Main Method, rendering either colorpicker or frameset depending on ->showPicker
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG;

		if(!t3lib_div::_GP('showPicker')) {	// Show frameset by default:
			$this->frameSet();
		} else {

				// Putting together the items into a form:
			$content = '
				<form name="colorform" method="post" action="wizard_colorpicker.php">
					'.$this->colorMatrix().'
					'.$this->colorList().'
					'.$this->colorImage().'

						<!-- Value box: -->
					<p class="c-head">'.$LANG->getLL('colorpicker_colorValue',1).'</p>
					<table border="0" cellpadding="0" cellspacing="3">
						<tr>
							<td><input type="text" '.$this->doc->formWidth(7).' maxlength="10" name="colorValue" value="'.htmlspecialchars($this->colorValue).'" /></td>
							<td style="background-color:'.htmlspecialchars($this->colorValue).'; border: 1px solid black;">&nbsp;<span style="color: black;">'.$LANG->getLL('colorpicker_black',1).'</span>&nbsp;<span style="color: white;">'.$LANG->getLL('colorpicker_white',1).'</span>&nbsp;</td>
							<td><input type="submit" name="save_close" value="'.$LANG->getLL('colorpicker_setClose',1).'" /></td>
						</tr>
					</table>

						<!-- Hidden fields with values that has to be kept constant -->
					<input type="hidden" name="showPicker" value="1" />
					<input type="hidden" name="fieldChangeFunc" value="'.htmlspecialchars($this->fieldChangeFunc).'" />
					<input type="hidden" name="fieldName" value="'.htmlspecialchars($this->fieldName).'" />
					<input type="hidden" name="formName" value="'.htmlspecialchars($this->formName).'" />
					<input type="hidden" name="md5ID" value="'.htmlspecialchars($this->md5ID).'" />
					<input type="hidden" name="exampleImg" value="'.htmlspecialchars($this->exampleImg).'" />
				</form>';

				// If the save/close button is clicked, then close:
			if(t3lib_div::_GP('save_close')) {
				$content.=$this->doc->wrapScriptTags('
					setValue(\''.$this->colorValue.'\');
					parent.close();
				');
			}

				// Output:
			$this->content.=$this->doc->section($LANG->getLL('colorpicker_title'), $content, 0,1);
		}
	}

	/**
	 * Returnes the sourcecode to the browser
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Returnes a frameset so our JavaScript Reference isn't lost
	 * Took some brains to figure this one out ;-)
	 * If Peter wouldn't have been I would've gone insane...
	 *
	 * @return	void
	 */
	function frameSet() {
		global $LANG;

			// Set doktype:
		$GLOBALS['TBE_TEMPLATE']->docType = 'xhtml_frames';
		$GLOBALS['TBE_TEMPLATE']->JScode = $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
				if (!window.opener)	{
					alert("ERROR: Sorry, no link to main window... Closing");
					close();
				}
		');

		$this->content = $GLOBALS['TBE_TEMPLATE']->startPage($LANG->getLL('colorpicker_title'));

			// URL for the inner main frame:
		$url = 'wizard_colorpicker.php?showPicker=1'.
				'&colorValue='.rawurlencode($this->P['currentValue']).
				'&fieldName='.rawurlencode($this->P['itemName']).
				'&formName='.rawurlencode($this->P['formName']).
				'&exampleImg='.rawurlencode($this->P['exampleImg']).
				'&md5ID='.rawurlencode($this->P['md5ID']).
				'&fieldChangeFunc='.rawurlencode(serialize($this->P['fieldChangeFunc']));

		$this->content.='
			<frameset rows="*,1" framespacing="0" frameborder="0" border="0">
				<frame name="content" src="'.htmlspecialchars($url).'" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
				<frame name="menu" src="dummy.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
			</frameset>
		';

		$this->content.='
</html>';
	}















	/************************************
	 *
	 * Rendering of various color selectors
	 *
	 ************************************/

	/**
	 * Creates a color matrix table
	 *
	 * @return	void
	 */
	function colorMatrix()	{
		global $LANG;

		$steps = 51;

			// Get colors:
		$color = array();

		for($rr=0;$rr<256;$rr+=$steps)	{
			for($gg=0;$gg<256;$gg+=$steps)	{
				for($bb=0;$bb<256;$bb+=$steps)	{
					$color[] = '#'.
						substr('0'.dechex($rr),-2).
						substr('0'.dechex($gg),-2).
						substr('0'.dechex($bb),-2);
				}
			}
		}

			// Traverse colors:
		$columns = 24;

		$rows = 0;
		$tRows = array();
		while(isset($color[$columns*$rows]))	{
			$tCells = array();
			for($i=0;$i<$columns;$i++)	{
				$tCells[] = '
					<td bgcolor="'.$color[$columns*$rows+$i].'" onclick="document.colorform.colorValue.value = \''.$color[$columns*$rows+$i].'\'; document.colorform.submit();" title="'.$color[$columns*$rows+$i].'">&nbsp;&nbsp;</td>';
			}
			$tRows[] = '
				<tr>'.implode('',$tCells).'
				</tr>';
			$rows++;
		}

		$table = '
			<p class="c-head">'.$LANG->getLL('colorpicker_fromMatrix',1).'</p>
			<table border="0" cellpadding="1" cellspacing="1" style="width:100%; border: 1px solid black; cursor:crosshair;">'.implode('',$tRows).'
			</table>';

		return $table;
	}

	/**
	 * Creates a selector box with all HTML color names.
	 *
	 * @return	void
	 */
	function colorList()	{
		global $LANG;

			// Initialize variables:
		$colors = explode(',',$this->HTMLcolorList);
		$currentValue = strtolower($this->colorValue);
		$opt = array();
		$opt[] = '<option value=""></option>';

			// Traverse colors, making option tags for selector box.
		foreach($colors as $colorName)	{
			$opt[] = '<option style="background-color: '.$colorName.';" value="'.htmlspecialchars($colorName).'"'.($currentValue==$colorName ? ' selected="selected"' : '').'>'.htmlspecialchars($colorName).'</option>';
		}

			// Compile selector box and return result:
		$output = '
			<p class="c-head">'.$LANG->getLL('colorpicker_fromList',1).'</p>
			<select onchange="document.colorform.colorValue.value = this.options[this.selectedIndex].value; document.colorform.submit(); return false;">
				'.implode('
				',$opt).'
			</select><br />';

		return $output;
	}

	/**
	 * Creates a color image selector
	 *
	 * @return	void
	 */
	function colorImage()	{
		global $LANG;

			// Handling color-picker image if any:
		if (!$this->imageError)	{
			if ($this->pickerImage)	{
				if(t3lib_div::_POST('coords_x')) {
					$this->colorValue = '#'.$this->getIndex(t3lib_stdgraphic::imageCreateFromFile($this->pickerImage),t3lib_div::_POST('coords_x'),t3lib_div::_POST('coords_y'));
				}
				$pickerFormImage = '
				<p class="c-head">'.$LANG->getLL('colorpicker_fromImage',1).'</p>
				<input type="image" src="../'.substr($this->pickerImage,strlen(PATH_site)).'" name="coords" style="cursor:crosshair;" /><br />';
			} else {
				$pickerFormImage = '';
			}
		} else {
			$pickerFormImage = '
			<p class="c-head">'.htmlspecialchars($this->imageError).'</p>';
		}

		return $pickerFormImage;
	}

	/**
	 * Gets the HTML (Hex) Color Code for the selected pixel of an image
	 * This method handles the correct imageResource no matter what format
	 *
	 * @param	pointer		Valid ImageResource returned by t3lib_stdgraphic::imageCreateFromFile
	 * @param	integer		X-Coordinate of the pixel that should be checked
	 * @param	integer		Y-Coordinate of the pixel that should be checked
	 * @return	string		HEX RGB value for color
	 * @see colorImage()
	 */
	function getIndex($im,$x,$y) {
		$rgb = ImageColorAt($im, $x, $y);
		$colorrgb = imagecolorsforindex($im,$rgb);
		$index['r'] = dechex($colorrgb['red']);
		$index['g'] = dechex($colorrgb['green']);
		$index['b'] = dechex($colorrgb['blue']);
		foreach ($index as $value) {
			if(strlen($value) == 1) {
				$hexvalue[] = strtoupper('0'.$value);
			} else {
				$hexvalue[] = strtoupper($value);
			}
		}
		$hex = implode('',$hexvalue);
		return $hex;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_colorpicker.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_colorpicker.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_wizard_colorpicker');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>